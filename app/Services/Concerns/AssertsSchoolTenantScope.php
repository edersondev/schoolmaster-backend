<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\TenantContextException;
use App\Models\School;

trait AssertsSchoolTenantScope
{
    private function assertSameSchool(?int $schoolId, School $school): void
    {
        if ($schoolId !== $school->id) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }
    }
}
