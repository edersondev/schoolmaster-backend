<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Models\AcademicPeriod;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use Illuminate\Database\Eloquent\Model;

final class AcademicPeriodLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof AcademicPeriod) {
            return;
        }
    }
}
