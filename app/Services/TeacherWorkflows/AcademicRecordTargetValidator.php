<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflows;

use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use Illuminate\Validation\ValidationException;

final class AcademicRecordTargetValidator
{
    /**
     * @return array{student: StudentProfile, period: AcademicPeriod}
     */
    public function validate(string $studentProfileUuid, string $academicPeriodUuid, int $schoolId): array
    {
        $student = StudentProfile::query()
            ->where('uuid', $studentProfileUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_profile_id' => ['The student profile must be active and belong to the resolved school.'],
            ]);
        }

        $period = AcademicPeriod::query()
            ->where('uuid', $academicPeriodUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'academic_period_id' => ['The academic period must be active and belong to the resolved school.'],
            ]);
        }

        return ['student' => $student, 'period' => $period];
    }
}
