<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use App\Models\School;

final class SchoolContextGuard
{
    public function requireResolved(TenantContext $context): School
    {
        if (! $context->isResolved() || $context->school === null) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        if ($context->school->status !== 'active') {
            throw new TenantContextException('Tenant context is inactive.');
        }

        return $context->school;
    }

    public function assertSameSchool(int $recordSchoolId, School $school): void
    {
        if ($recordSchoolId !== $school->id) {
            throw new TenantContextException('Tenant context is outside permitted scope.');
        }
    }
}
