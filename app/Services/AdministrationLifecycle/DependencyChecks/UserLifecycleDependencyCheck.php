<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Exceptions\ConflictException;
use App\Models\User;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Database\Eloquent\Model;

final class UserLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof User || ! in_array($action, [LifecycleAction::DEACTIVATE, LifecycleAction::DELETE], true)) {
            return;
        }

        if ($resource->studentProfile()->exists()) {
            throw new ConflictException('User has a linked student profile and cannot be changed by this lifecycle action.');
        }
    }
}
