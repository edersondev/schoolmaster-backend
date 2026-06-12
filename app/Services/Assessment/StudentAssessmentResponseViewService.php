<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\TenantContext;
use App\Exceptions\PermissionDeniedException;
use App\Models\AssessmentResponseAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class StudentAssessmentResponseViewService
{
    public function __construct(
        private readonly AssessmentTenantScopeService $tenantScope,
        private readonly AssessmentResponseVisibilityService $visibility,
        private readonly AssessmentAuditService $audit,
    ) {}

    public function get(User $actor, TenantContext $tenantContext, string $attemptUuid): AssessmentResponseAttempt
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

        $attempt = AssessmentResponseAttempt::query()
            ->with(['school', 'studentProfile', 'questionnaire', 'learningSet', 'academicPeriod', 'answers.question', 'answers.fileAttachment', 'gradingOutcomes'])
            ->where('uuid', $attemptUuid)
            ->where('school_id', $context->school->id)
            ->where('student_profile_id', $student->id)
            ->first();

        if ($attempt === null) {
            throw (new ModelNotFoundException)->setModel(AssessmentResponseAttempt::class, [$attemptUuid]);
        }

        if (! $this->visibility->studentCanView($actor, $attempt)) {
            throw (new ModelNotFoundException)->setModel(AssessmentResponseAttempt::class, [$attemptUuid]);
        }

        $this->audit->record($context, 'visibility', 'succeeded', 'student_response_viewed', $attempt);

        return $attempt;
    }
}
