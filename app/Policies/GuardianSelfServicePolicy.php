<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class GuardianSelfServicePolicy
{
    public function view(User $user, School $school): bool
    {
        return $user->isActive() && ($user->school_id === null || $user->school_id === $school->id);
    }
}
