<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesStudentAdministration
{
    private function assertStudentPermission(User $actor, School $school, string $permission): void
    {
        if (! $actor->hasSchoolPermission($permission, $school->id)) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }

    private function assertCanViewStudentProfiles(User $actor, School $school): void
    {
        $this->assertStudentPermission($actor, $school, 'student_profiles.view');
    }

    private function assertCanManageStudentProfiles(User $actor, School $school): void
    {
        $this->assertStudentPermission($actor, $school, 'student_profiles.manage');
    }

    private function assertCanTransferStudentProfiles(User $actor, School $school): void
    {
        $this->assertStudentPermission($actor, $school, 'student_transfers.manage');
    }
}
