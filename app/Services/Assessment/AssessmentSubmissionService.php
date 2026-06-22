<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentAnswerInput;
use App\DTOs\Assessment\AssessmentResponseSubmissionData;
use App\DTOs\TenantContext;
use App\Models\AssessmentResponseAttempt;
use App\Models\QuestionnaireQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssessmentSubmissionService
{
    public function __construct(
        private readonly AssessmentSubmissionEligibilityService $eligibility,
        private readonly LongTextAnswerService $longText,
        private readonly FileResponseSubmissionService $files,
        private readonly AssessmentResponseStateService $state,
        private readonly AssessmentAuditService $audit,
    ) {}

    public function submit(User $actor, TenantContext $tenantContext, AssessmentResponseSubmissionData $data): AssessmentResponseAttempt
    {
        $eligible = $this->eligibility->resolve($actor, $tenantContext, $data);
        $questions = $eligible['questionnaire']->questions->keyBy('uuid');

        $this->assertAnswersMatchQuestions($data->answers, $questions->all());

        return DB::transaction(function () use ($data, $eligible, $questions): AssessmentResponseAttempt {
            $attempt = AssessmentResponseAttempt::query()->create([
                'school_id' => $eligible['context']->school->id,
                'student_profile_id' => $eligible['student']->id,
                'questionnaire_id' => $eligible['questionnaire']->id,
                'learning_set_id' => $eligible['learning_set']->id,
                'academic_period_id' => $eligible['learning_set']->academic_period_id,
                'submission_state' => 'submitted',
                'grading_status' => 'needs_review',
                'submitted_at' => now(),
            ]);

            $fileCount = 0;

            foreach ($data->answers as $input) {
                /** @var QuestionnaireQuestion $question */
                $question = $questions->get($input->questionId);
                $answer = $attempt->answers()->create([
                    'school_id' => $attempt->school_id,
                    'questionnaire_question_id' => $question->id,
                    'question_type' => $question->question_type,
                    'answer_text' => $this->answerText($question, $input),
                    'answer_metadata' => null,
                    'validation_status' => 'accepted',
                    'grading_status' => 'needs_review',
                    'visibility_state' => $question->question_type === 'file_response' ? 'unavailable' : 'student_safe',
                ]);

                if ($question->question_type === 'file_response' && $input->file !== null) {
                    $this->files->persist($answer->load('school'), $input->file);
                    $fileCount++;
                }
            }

            $attempt = $this->state->refreshFromAnswers($attempt);
            $this->audit->record(
                $eligible['context'],
                'submission',
                'succeeded',
                'response_submitted',
                $attempt,
                ['answer_count' => count($data->answers)],
            );

            if ($fileCount > 0) {
                $this->audit->record(
                    $eligible['context'],
                    'upload',
                    'succeeded',
                    'file_response_uploaded',
                    $attempt,
                    ['file_count' => $fileCount],
                );
            }

            return $attempt->refresh()->load(['school', 'studentProfile', 'questionnaire', 'learningSet', 'academicPeriod', 'answers.question', 'answers.fileAttachment']);
        });
    }

    /**
     * @param  list<AssessmentAnswerInput>  $answers
     * @param  array<string, QuestionnaireQuestion>  $questions
     */
    private function assertAnswersMatchQuestions(array $answers, array $questions): void
    {
        $submitted = array_map(fn (AssessmentAnswerInput $answer): string => $answer->questionId, $answers);
        $expected = array_keys($questions);
        sort($submitted);
        sort($expected);

        if ($submitted !== $expected) {
            throw ValidationException::withMessages([
                'answers' => ['A response must include exactly one answer for every questionnaire question.'],
            ]);
        }

        foreach ($answers as $answer) {
            $question = $questions[$answer->questionId] ?? null;

            if ($question === null || $question->question_type !== $answer->questionType) {
                throw ValidationException::withMessages([
                    'answers' => ['Submitted answer question types must match the questionnaire.'],
                ]);
            }
        }
    }

    private function answerText(QuestionnaireQuestion $question, AssessmentAnswerInput $input): ?string
    {
        return match ($question->question_type) {
            'long_text' => $this->longText->normalize((string) $input->answerText),
            'short_text', 'multiple_choice', 'true_false' => $this->legacyText($input),
            'file_response' => $this->fileAnswer($input),
            default => throw ValidationException::withMessages(['answers' => ['Unsupported question type.']]),
        };
    }

    private function legacyText(AssessmentAnswerInput $input): string
    {
        if ($input->answerText === null || trim($input->answerText) === '') {
            throw ValidationException::withMessages([
                'answers' => ['Legacy questionnaire answers require answer_text.'],
            ]);
        }

        return $input->answerText;
    }

    private function fileAnswer(AssessmentAnswerInput $input): ?string
    {
        if ($input->file === null) {
            throw ValidationException::withMessages([
                'answers' => ['File-response answers require exactly one uploaded file.'],
            ]);
        }

        if ($input->answerText !== null) {
            throw ValidationException::withMessages([
                'answers' => ['File-response answers must not include answer_text.'],
            ]);
        }

        return null;
    }
}
