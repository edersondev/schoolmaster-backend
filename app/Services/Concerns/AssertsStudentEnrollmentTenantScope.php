<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\TenantContextException;
use App\Models\School;
use App\Models\StudentProfile;

trait AssertsStudentEnrollmentTenantScope
{
    private function assertStudentProfileInSchool(StudentProfile $studentProfile, School $school): void
    {
        if ((int) $studentProfile->school_id !== (int) $school->id) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }
    }

    private function assertSameSchoolId(?int $schoolId, School $school): void
    {
        if ($schoolId === null || (int) $schoolId !== (int) $school->id) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }
    }
}
