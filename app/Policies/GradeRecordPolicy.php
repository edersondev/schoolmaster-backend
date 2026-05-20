<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GradeRecord;
use App\Models\User;

final class GradeRecordPolicy
{
    public function view(User $user, GradeRecord $record): bool
    {
        return $user->hasSchoolPermission('grades.view', $record->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('grades.manage', $schoolId);
    }
}
