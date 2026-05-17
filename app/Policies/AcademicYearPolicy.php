<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class AcademicYearPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('academic_years.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('academic_years.manage', $school->id);
    }
}
