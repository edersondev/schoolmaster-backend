<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Database\Eloquent\Model;

final class SchoolLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof School || $action === LifecycleAction::RESTORE) {
            return;
        }

        if (in_array($action, [LifecycleAction::DEACTIVATE, LifecycleAction::DELETE], true)
            && $resource->users()->where('status', 'active')->exists()) {
            throw new ConflictException('School has active users and cannot be changed by this lifecycle action.');
        }
    }
}
