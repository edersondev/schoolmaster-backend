<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentActorContext;
use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\User;
use Illuminate\Support\Str;

final class AssessmentTenantScopeService
{
    public function actorContext(User $actor, TenantContext $tenantContext, ?string $authority = null): AssessmentActorContext
    {
        if (! $tenantContext->isResolved()) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        return new AssessmentActorContext(
            actor: $actor,
            school: $tenantContext->school,
            authority: $authority ?? $this->resolveAuthority($actor, $tenantContext->school->id),
            correlationId: (string) Str::uuid(),
        );
    }

    private function resolveAuthority(User $actor, int $schoolId): string
    {
        if ($actor->hasSchoolPermission('users.manage', $schoolId)) {
            return 'school_administrator';
        }

        if ($actor->hasSchoolPermission('questionnaires.manage', $schoolId)) {
            return 'teacher';
        }

        if ($actor->studentProfile !== null && $actor->studentProfile->school_id === $schoolId) {
            return 'student';
        }

        return 'none';
    }
}
