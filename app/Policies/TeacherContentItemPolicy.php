<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherContentItem;
use App\Models\User;

final class TeacherContentItemPolicy
{
    public function view(User $user, TeacherContentItem $content): bool
    {
        return $this->canManage($user, $content);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('teacher_content.manage', $schoolId);
    }

    public function update(User $user, TeacherContentItem $content): bool
    {
        return $this->canManage($user, $content);
    }

    public function lifecycle(User $user, TeacherContentItem $content): bool
    {
        return $this->canManage($user, $content);
    }

    public function download(User $user, TeacherContentItem $content): bool
    {
        return $this->canManage($user, $content) && $content->status === 'active' && $content->scan_status === 'clean';
    }

    private function canManage(User $user, TeacherContentItem $content): bool
    {
        if ($user->school_id !== $content->school_id) {
            return false;
        }

        if ($user->id === $content->owner_user_id && $user->hasSchoolPermission('teacher_content.manage', $content->school_id)) {
            return true;
        }

        return $user->hasSchoolPermission('users.manage', $content->school_id);
    }
}
