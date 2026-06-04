<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AcademicRecordPolicy
{
    public function view(User $user, GradeRecord|AttendanceRecord $record): bool
    {
        return $this->canManage($user, $record);
    }

    public function correct(User $user, GradeRecord|AttendanceRecord $record): bool
    {
        return $this->canManage($user, $record);
    }

    public function lifecycle(User $user, GradeRecord|AttendanceRecord $record): bool
    {
        return $this->canManage($user, $record);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('grades.manage', $schoolId)
            || $user->hasSchoolPermission('attendance.manage', $schoolId);
    }

    private function canManage(User $user, Model $record): bool
    {
        if ($user->school_id !== $record->school_id) {
            return false;
        }

        if ($user->hasSchoolPermission('users.manage', $record->school_id)) {
            return true;
        }

        $permission = $record instanceof GradeRecord ? 'grades.manage' : 'attendance.manage';

        return $user->id === $record->recorded_by_user_id
            && $user->hasSchoolPermission($permission, $record->school_id);
    }
}
