<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class AcademicPeriodPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('academic_periods.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('academic_periods.manage', $school->id);
    }
}
