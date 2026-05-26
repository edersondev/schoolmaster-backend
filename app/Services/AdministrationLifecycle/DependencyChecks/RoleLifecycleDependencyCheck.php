<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Exceptions\ConflictException;
use App\Models\Role;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Database\Eloquent\Model;

final class RoleLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof Role || ! in_array($action, [LifecycleAction::DEACTIVATE, LifecycleAction::DELETE], true)) {
            return;
        }

        if ($resource->users()->where('users.status', 'active')->exists()) {
            throw new ConflictException('Role is assigned to active users and cannot be changed by this lifecycle action.');
        }
    }
}
