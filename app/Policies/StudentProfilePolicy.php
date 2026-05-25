<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;

final class StudentProfilePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('student_profiles.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('student_profiles.manage', $school->id);
    }

    public function view(User $user, StudentProfile $studentProfile): bool
    {
        return $user->hasSchoolPermission('student_profiles.view', $studentProfile->school_id);
    }

    public function updateStatus(User $user, StudentProfile $studentProfile): bool
    {
        return $user->hasSchoolPermission('student_profiles.manage', $studentProfile->school_id);
    }

    public function transfer(User $user, StudentProfile $studentProfile): bool
    {
        return $user->hasSchoolPermission('student_transfers.manage', $studentProfile->school_id);
    }
}
