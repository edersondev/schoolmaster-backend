<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class ClassroomRosterPolicy
{
    public const MANAGE_PERMISSION = 'classroom_rosters.manage';

    public function manage(User $user, School $school): bool
    {
        return $user->status === 'active'
            && $user->hasSchoolPermission(self::MANAGE_PERMISSION, $school->id);
    }

    public function viewSchoolRoster(User $user, School $school): bool
    {
        return $this->manage($user, $school);
    }

    public function viewOwnActiveTeacherAssignment(User $user, int $teacherUserId, string $assignmentStatus): bool
    {
        return $user->status === 'active'
            && $user->id === $teacherUserId
            && $assignmentStatus === 'active';
    }
}
