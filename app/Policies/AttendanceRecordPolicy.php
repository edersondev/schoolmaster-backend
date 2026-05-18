<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

final class AttendanceRecordPolicy
{
    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->hasSchoolPermission('attendance.view', $record->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('attendance.manage', $schoolId);
    }
}
