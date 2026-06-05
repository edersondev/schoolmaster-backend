<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class GuardianPolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('guardians.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('guardians.manage', $school->id);
    }

    public function manageUserLinks(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('guardians.manage', $school->id);
    }
}
