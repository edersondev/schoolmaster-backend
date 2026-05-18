<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\PermissionDeniedException;
use App\Models\School;
use App\Models\User;

trait AuthorizesTeacherWorkflows
{
    private function assertTeacherWorkflowPermission(User $actor, School $school, string $permission): void
    {
        if (! $actor->hasSchoolPermission($permission, $school->id)) {
            throw new PermissionDeniedException('The authenticated user lacks permission for this action.');
        }
    }
}
