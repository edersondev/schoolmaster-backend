<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentActorContext;
use App\DTOs\Assessment\AssessmentResponseSubmissionData;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Exceptions\PermissionDeniedException;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\StudentProfile;
use App\Models\User;

final class AssessmentSubmissionEligibilityService
{
    public function __construct(private readonly AssessmentTenantScopeService $tenantScope) {}

    /**
     * @return array{context:AssessmentActorContext, student:StudentProfile, questionnaire:Questionnaire, learning_set:LearningSet}
     */
    public function resolve(User $actor, TenantContext $tenantContext, AssessmentResponseSubmissionData $data): array
    {
        $context = $this->tenantScope->actorContext($actor, $tenantContext, 'student');
        $student = StudentProfile::query()
            ->where('school_id', $context->school->id)
            ->where('user_id', $actor->id)
            ->where('status', 'active')
            ->first();

        if ($student === null) {
            throw new PermissionDeniedException('The authenticated user lacks an active student profile in the resolved school.');
        }

        $questionnaire = Questionnaire::query()
            ->with('questions')
            ->where('uuid', $data->questionnaireId)
            ->where('school_id', $context->school->id)
            ->where('status', 'active')
            ->first();

        $learningSet = LearningSet::query()
            ->with('academicPeriod')
            ->where('uuid', $data->learningSetId)
            ->where('school_id', $context->school->id)
            ->whereIn('status', ['published', 'active'])
            ->whereHas('academicPeriod', fn ($query) => $query->where('status', 'active'))
            ->first();

        if ($questionnaire === null || $learningSet === null) {
            throw new PermissionDeniedException('The assessment is not available in the resolved school.');
        }

        $isAssigned = $learningSet->assignments()
            ->where('student_profile_id', $student->id)
            ->where('status', 'active')
            ->exists();
        $containsQuestionnaire = $learningSet->entries()
            ->where('entry_type', 'questionnaire')
            ->where('entry_reference_id', $questionnaire->id)
            ->exists();

        if (! $isAssigned || ! $containsQuestionnaire) {
            throw new PermissionDeniedException('The assessment is not assigned to the authenticated student.');
        }

        if ($learningSet->due_at !== null && $learningSet->due_at->isPast()) {
            throw new ConflictException('The assessment response submission window is closed.');
        }

        $duplicate = AssessmentResponseAttempt::query()
            ->where('school_id', $context->school->id)
            ->where('student_profile_id', $student->id)
            ->where('questionnaire_id', $questionnaire->id)
            ->where('learning_set_id', $learningSet->id)
            ->exists();

        if ($duplicate) {
            throw new ConflictException('The student has already submitted this assessment response.');
        }

        return [
            'context' => $context,
            'student' => $student,
            'questionnaire' => $questionnaire,
            'learning_set' => $learningSet,
        ];
    }
}
