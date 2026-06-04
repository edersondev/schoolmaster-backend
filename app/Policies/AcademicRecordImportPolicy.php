<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class AcademicRecordImportPolicy
{
    public function import(User $user, int $schoolId): bool
    {
        return $user->school_id === $schoolId
            && $user->hasSchoolPermission('users.manage', $schoolId);
    }
}
