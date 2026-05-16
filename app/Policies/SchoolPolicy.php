<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

final class SchoolPolicy extends ScopePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->platform($user, 'schools.view');
    }

    public function view(User $user, School $school): bool
    {
        return $this->platform($user, 'schools.view');
    }

    public function create(User $user): bool
    {
        return $this->platform($user, 'schools.manage');
    }

    public function update(User $user, School $school): bool
    {
        return $this->platform($user, 'schools.manage');
    }
}
