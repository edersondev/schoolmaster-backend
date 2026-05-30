<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherAssignment;
use App\Models\User;

final class TeacherAssignmentPolicy
{
    public function view(User $user, TeacherAssignment $assignment): bool
    {
        return $user->hasSchoolPermission(ClassroomRosterPolicy::MANAGE_PERMISSION, $assignment->school_id)
            || ($assignment->status === 'active' && $assignment->teacher_user_id === $user->id);
    }

    public function manage(User $user, TeacherAssignment $assignment): bool
    {
        return $user->hasSchoolPermission(ClassroomRosterPolicy::MANAGE_PERMISSION, $assignment->school_id);
    }
}
