<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentProfile;
use App\Models\TeacherContentItem;
use App\Models\User;

final class StudentTeacherContentPolicy
{
    public function download(User $user, TeacherContentItem $content, StudentProfile $studentProfile): bool
    {
        return $studentProfile->user_id === $user->id
            && $content->school_id === $studentProfile->school_id
            && $content->isAvailable();
    }
}
