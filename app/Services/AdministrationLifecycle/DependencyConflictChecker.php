<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use Illuminate\Database\Eloquent\Model;

interface DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void;
}
