<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class TeacherWorkflowPolicy
{
    public function canManageOwnedRecord(
        User $actor,
        int $schoolId,
        int $ownerUserId,
        string $ownerPermission,
        string $administratorPermission = 'users.manage',
    ): bool {
        if ($actor->school_id !== $schoolId) {
            return false;
        }

        if ($actor->id === $ownerUserId && $actor->hasSchoolPermission($ownerPermission, $schoolId)) {
            return true;
        }

        return $actor->hasSchoolPermission($administratorPermission, $schoolId);
    }

    public function deniesCrossTenant(User $actor, int $schoolId): bool
    {
        return $actor->school_id !== $schoolId;
    }
}
