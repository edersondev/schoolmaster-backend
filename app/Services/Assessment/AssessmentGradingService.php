<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentGradingOutcome;
use App\Models\AssessmentResponseAttempt;
use App\Models\User;
use App\Repositories\AssessmentQueryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssessmentGradingService
{
    public function __construct(
        private readonly AssessmentTenantScopeService $tenantScope,
        private readonly AssessmentReviewAuthorizationService $authorization,
        private readonly AssessmentResponseStateService $state,
        private readonly AssessmentAuditService $audit,
        private readonly AssessmentQueryRepository $repository,
    ) {}

    public function grade(User $actor, TenantContext $tenantContext, string $attemptUuid, array $outcomes): AssessmentResponseAttempt
    {
        $context = $this->tenantScope->actorContext($actor, $tenantContext);
        $attempt = $this->repository->findAttempt($attemptUuid, $context->school->id);

        if ($attempt === null) {
            throw (new ModelNotFoundException)->setModel(AssessmentResponseAttempt::class, [$attemptUuid]);
        }

        $this->authorization->assertCanReview($actor, $attempt);

        return DB::transaction(function () use ($actor, $attempt, $context, $outcomes): AssessmentResponseAttempt {
            $answers = $attempt->answers->keyBy('uuid');

            foreach ($outcomes as $outcome) {
                /** @var AssessmentAnswer|null $answer */
                $answer = $answers->get($outcome['answer_id']);

                if ($answer === null) {
                    throw ValidationException::withMessages(['grading_outcomes' => ['Every grading outcome must reference an answer in this response.']]);
                }

                $status = $outcome['status'];
                $score = array_key_exists('score', $outcome) ? $outcome['score'] : null;
                $file = $answer->fileAttachment;

                if ($file?->scan_status === 'pending') {
                    throw new ConflictException('Pending-scan file responses cannot be graded.');
                }

                if ($file?->scan_status === 'failed' && ! ($status === 'exempted' || ((float) $score === 0.0 && $status === 'graded'))) {
                    throw ValidationException::withMessages(['grading_outcomes' => ['Failed-scan file responses may only be graded as zero or exempted.']]);
                }

                if ($status === 'graded' && $score === null) {
                    throw ValidationException::withMessages(['grading_outcomes' => ['A graded outcome requires a score.']]);
                }

                AssessmentGradingOutcome::query()->create([
                    'school_id' => $attempt->school_id,
                    'assessment_response_attempt_id' => $attempt->id,
                    'assessment_answer_id' => $answer->id,
                    'grader_user_id' => $actor->id,
                    'grading_status' => $status,
                    'score' => $score,
                    'outcome' => $status,
                    'feedback_summary' => $outcome['feedback_summary'] ?? null,
                    'private_grading_note' => $outcome['private_grading_note'] ?? null,
                    'graded_at' => now(),
                ]);

                $answer->forceFill([
                    'grading_status' => $status,
                    'visibility_state' => $status === 'returned' ? 'teacher_review' : $answer->visibility_state,
                ])->save();
            }

            $graded = $this->state->refreshFromAnswers($attempt);
            $latestOutcomes = $graded->gradingOutcomes
                ->whereNotNull('assessment_answer_id')
                ->sortByDesc('id')
                ->unique('assessment_answer_id');
            $graded->forceFill([
                'earned_points' => $latestOutcomes->whereNotNull('score')->sum(fn (AssessmentGradingOutcome $outcome): float => (float) $outcome->score),
                'possible_points' => max(1, $graded->answers()->count()) * 100,
            ])->save();
            $this->audit->record($context, 'grading', 'succeeded', 'manual_grading_recorded', $graded, [
                'outcome_count' => count($outcomes),
            ]);

            return $graded->refresh()->load(['school', 'studentProfile', 'questionnaire', 'learningSet', 'academicPeriod', 'answers.question', 'answers.fileAttachment', 'gradingOutcomes.answer', 'gradingOutcomes.grader']);
        });
    }
}
