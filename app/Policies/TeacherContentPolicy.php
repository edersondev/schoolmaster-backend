<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherContentItem;
use App\Models\User;

final class TeacherContentPolicy
{
    public function view(User $user, TeacherContentItem $content): bool
    {
        return $user->hasSchoolPermission('teacher_content.view', $content->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('teacher_content.manage', $schoolId);
    }
}
