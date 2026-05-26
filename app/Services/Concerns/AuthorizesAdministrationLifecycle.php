<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesAdministrationLifecycle
{
    private function assertPlatformLifecyclePermission(User $actor, string $permission = 'schools.manage'): void
    {
        if (! $actor->hasPermission($permission, 'platform')) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }

    private function assertSchoolLifecyclePermission(User $actor, School $school, string $permission): void
    {
        if (! $actor->hasSchoolPermission($permission, $school->id)) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }
}
