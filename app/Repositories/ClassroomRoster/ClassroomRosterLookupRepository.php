<?php

declare(strict_types=1);

namespace App\Repositories\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ClassroomRosterLookupRepository
{
    public function academicPeriodForSchool(string $academicPeriodUuid, int $schoolId): ?AcademicPeriod
    {
        return AcademicPeriod::query()
            ->where('uuid', $academicPeriodUuid)
            ->where('school_id', $schoolId)
            ->first();
    }

    public function studentProfileForSchool(string $studentProfileUuid, int $schoolId): ?StudentProfile
    {
        return StudentProfile::query()
            ->where('uuid', $studentProfileUuid)
            ->where('school_id', $schoolId)
            ->first();
    }

    public function teacherForSchool(string $teacherUuid, int $schoolId): ?User
    {
        return User::query()
            ->where('uuid', $teacherUuid)
            ->where('school_id', $schoolId)
            ->first();
    }

    public function classSectionForSchool(string $classSectionUuid, int $schoolId): ?Model
    {
        if (! class_exists(\App\Models\ClassSection::class)) {
            return null;
        }

        return \App\Models\ClassSection::query()
            ->where('uuid', $classSectionUuid)
            ->where('school_id', $schoolId)
            ->first();
    }

    public function hasActiveRosterMemberships(int $classSectionId): bool
    {
        if (! class_exists(\App\Models\RosterMembership::class)) {
            return false;
        }

        return \App\Models\RosterMembership::query()
            ->where('class_section_id', $classSectionId)
            ->where('status', 'active')
            ->exists();
    }

    public function hasActiveTeacherAssignments(int $classSectionId): bool
    {
        if (! class_exists(\App\Models\TeacherAssignment::class)) {
            return false;
        }

        return \App\Models\TeacherAssignment::query()
            ->where('class_section_id', $classSectionId)
            ->where('status', 'active')
            ->exists();
    }
}
