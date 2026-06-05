<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianActorContext;
use App\DTOs\GuardianSelfService\GuardianStudentTarget;
use App\DTOs\TenantContext;
use App\Models\GuardianAssociation;
use App\Models\GuardianUserLink;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class GuardianAccessResolver
{
    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function resolveActor(User $user, TenantContext $context): GuardianActorContext
    {
        $school = $this->tenantContext->requireSchool($context);

        if (! Gate::forUser($user)->allows('guardian-self-service.view', $school)) {
            throw new AuthorizationException('The authenticated user lacks guardian self-service access.');
        }

        $link = GuardianUserLink::query()
            ->with(['guardian', 'user', 'school'])
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('guardian', fn ($query) => $query
                ->where('school_id', $school->id)
                ->where('status', 'active'))
            ->first();

        if ($link === null || $link->guardian === null) {
            throw new AuthorizationException('The authenticated user lacks an active guardian link in the resolved school.');
        }

        return new GuardianActorContext($user, $school, $link->guardian, $link);
    }

    public function resolveTarget(GuardianActorContext $actor, string $studentUuid): GuardianStudentTarget
    {
        $student = StudentProfile::query()
            ->where('uuid', $studentUuid)
            ->where('school_id', $actor->school->id)
            ->where('status', 'active')
            ->first();

        if ($student === null) {
            throw (new ModelNotFoundException)->setModel(StudentProfile::class);
        }

        $association = GuardianAssociation::query()
            ->where(function ($query) use ($actor): void {
                $query
                    ->where('school_id', $actor->school->id)
                    ->orWhereNull('school_id');
            })
            ->where('guardian_id', $actor->guardian->id)
            ->where('student_profile_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($association === null) {
            throw (new ModelNotFoundException)->setModel(StudentProfile::class);
        }

        return new GuardianStudentTarget(
            actor: $actor,
            student: $student,
            relationshipLabel: (string) ($association->relationship_type ?: $actor->guardian->relationship_type),
        );
    }
}
