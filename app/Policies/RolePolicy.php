<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\School;
use App\Models\User;

final class RolePolicy
{
    public function viewAny(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('roles.view', $school->id);
    }

    public function create(User $user, School $school): bool
    {
        return $user->hasSchoolPermission('roles.manage', $school->id);
    }

    public function view(User $user, Role $role): bool
    {
        return $role->school_id !== null && $user->hasSchoolPermission('roles.view', $role->school_id);
    }
}
