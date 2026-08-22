<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\School;
use App\Models\User;
use App\Services\AdministrationLifecycle\LifecycleAction;
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

    private function assertSchoolLifecycleActionPermission(
        User $actor,
        School $school,
        string $resourceType,
        string $action,
        string $permissionPrefix,
    ): void {
        if ($resourceType === 'users' && $action === LifecycleAction::RESTORE) {
            $this->assertSchoolLifecyclePermission($actor, $school, 'users.view');
            $this->assertSchoolLifecyclePermission($actor, $school, 'users.manage');

            return;
        }

        $this->assertSchoolLifecyclePermission($actor, $school, "{$permissionPrefix}.lifecycle");
    }
}
