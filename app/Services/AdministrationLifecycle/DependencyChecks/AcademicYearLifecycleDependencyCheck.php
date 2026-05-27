<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle\DependencyChecks;

use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Services\AdministrationLifecycle\DependencyConflictChecker;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Database\Eloquent\Model;

final class AcademicYearLifecycleDependencyCheck implements DependencyConflictChecker
{
    public function assertNoConflicts(Model $resource, string $action): void
    {
        if (! $resource instanceof AcademicYear || ! in_array($action, [LifecycleAction::DEACTIVATE, LifecycleAction::DELETE], true)) {
            return;
        }

        if ($resource->periods()->where('status', 'active')->exists()) {
            throw new ConflictException('Academic year has active periods and cannot be changed by this lifecycle action.');
        }
    }
}
