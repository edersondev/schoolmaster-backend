<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\LifecycleInput;
use App\DTOs\TenantContext;
use App\Models\Questionnaire;
use App\Models\User;
use App\Repositories\TeacherWorkflow\TeacherWorkflowLookupRepository;
use App\Services\Questionnaires\QuestionnaireValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class QuestionnaireService
{
    public function __construct(
        private readonly SchoolContextGuard $schoolContext,
        private readonly TeacherWorkflowLookupRepository $lookup,
        private readonly HistoricalMeaningGuard $historicalMeaning,
        private readonly LifecycleTransitionService $lifecycle,
        private readonly TeacherWorkflowAuditLogger $audit,
        private readonly QuestionnaireValidator $validator,
    ) {}

    public function get(User $actor, TenantContext $context, string $questionnaireUuid): Questionnaire
    {
        $questionnaire = $this->resolve($context, $questionnaireUuid);
        Gate::forUser($actor)->authorize('view', $questionnaire);

        return $questionnaire->load(['school', 'owner', 'questions']);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function update(User $actor, TenantContext $context, string $questionnaireUuid, array $changes): Questionnaire
    {
        $questionnaire = $this->resolve($context, $questionnaireUuid);
        Gate::forUser($actor)->authorize('update', $questionnaire);
        $this->historicalMeaning->assertQuestionnaireEditable($questionnaire, $changes);
        if (isset($changes['questions']) && is_array($changes['questions'])) {
            $this->validator->validate($changes['questions']);
        }

        return DB::transaction(function () use ($actor, $changes, $questionnaire): Questionnaire {
            $questionChanges = $changes['questions'] ?? null;
            unset($changes['questions']);

            if ($changes !== []) {
                $questionnaire->forceFill($changes)->save();
            }

            if (is_array($questionChanges)) {
                $questionnaire->questions()->delete();

                foreach ($questionChanges as $question) {
                    $questionnaire->questions()->create([
                        'question_type' => $question['question_type'],
                        'prompt' => $question['prompt'],
                        'options' => $question['options'] ?? null,
                        'correct_answer' => $question['correct_answer'] ?? null,
                        'answer_schema' => $question['answer_schema'] ?? null,
                        'grading_rule' => $question['grading_rule'] ?? null,
                        'visibility' => $question['visibility'] ?? null,
                        'sequence' => $question['sequence'],
                    ]);
                }
            }

            $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $questionnaire->school_id, Questionnaire::class, $questionnaire->uuid, [
                'action' => 'update',
                'changed_fields' => array_keys($changes + (is_array($questionChanges) ? ['questions' => true] : [])),
            ]);

            return $questionnaire->refresh()->load(['school', 'owner', 'questions']);
        });
    }

    public function status(User $actor, TenantContext $context, string $questionnaireUuid, string $status): Questionnaire
    {
        $questionnaire = $this->resolve($context, $questionnaireUuid);
        Gate::forUser($actor)->authorize('lifecycle', $questionnaire);
        $input = $status === 'active' ? LifecycleInput::activate() : LifecycleInput::deactivate();
        $updated = $this->lifecycle->transition($questionnaire, $input);
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, Questionnaire::class, $updated->uuid, [
            'action' => $status,
        ]);

        return $updated->load(['school', 'owner', 'questions']);
    }

    public function delete(User $actor, TenantContext $context, string $questionnaireUuid): Questionnaire
    {
        $questionnaire = $this->resolve($context, $questionnaireUuid);
        Gate::forUser($actor)->authorize('lifecycle', $questionnaire);
        $questionnaire->forceFill(['deleted_by_user_id' => $actor->id])->save();
        $updated = $this->lifecycle->transition($questionnaire, LifecycleInput::delete());
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, Questionnaire::class, $updated->uuid, [
            'action' => 'delete',
        ]);

        return $updated->load(['school', 'owner', 'questions']);
    }

    public function restore(User $actor, TenantContext $context, string $questionnaireUuid): Questionnaire
    {
        $questionnaire = $this->resolve($context, $questionnaireUuid);
        Gate::forUser($actor)->authorize('lifecycle', $questionnaire);
        $updated = $this->lifecycle->transition($questionnaire, LifecycleInput::restore());
        $updated->forceFill([
            'restored_at' => now(),
            'restored_by_user_id' => $actor->id,
        ])->save();
        $this->audit->record('teacher_workflow.lifecycle', 'success', $actor->id, $updated->school_id, Questionnaire::class, $updated->uuid, [
            'action' => 'restore',
        ]);

        return $updated->refresh()->load(['school', 'owner', 'questions']);
    }

    private function resolve(TenantContext $context, string $uuid): Questionnaire
    {
        $school = $this->schoolContext->requireResolved($context);
        $questionnaire = $this->lookup->findQuestionnaire($uuid, $school->id);

        if ($questionnaire === null) {
            throw (new ModelNotFoundException)->setModel(Questionnaire::class, [$uuid]);
        }

        return $questionnaire;
    }
}
