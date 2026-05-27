<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Models\Guardian;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use Illuminate\Database\Eloquent\Model;

final class GuardianLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof Guardian) {
            return;
        }
    }
}
