<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ScopePolicy
{
    public function platform(User $user, string $permission): bool
    {
        return $user->hasPermission($permission, 'platform');
    }

    public function school(User $user, string $permission, int $schoolId): bool
    {
        return $user->school_id === $schoolId && $user->hasPermission($permission, 'school');
    }
}
