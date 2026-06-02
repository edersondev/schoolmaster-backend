<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningSet;
use App\Models\User;

final class LearningSetPolicy
{
    public function view(User $user, LearningSet $learningSet): bool
    {
        return $this->canManage($user, $learningSet);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('learning_sets.manage', $schoolId);
    }

    public function update(User $user, LearningSet $learningSet): bool
    {
        return $this->canManage($user, $learningSet);
    }

    public function lifecycle(User $user, LearningSet $learningSet): bool
    {
        return $this->canManage($user, $learningSet);
    }

    private function canManage(User $user, LearningSet $learningSet): bool
    {
        if ($user->school_id !== $learningSet->school_id) {
            return false;
        }

        if ($user->id === $learningSet->owner_user_id && $user->hasSchoolPermission('learning_sets.manage', $learningSet->school_id)) {
            return true;
        }

        return $user->hasSchoolPermission('users.manage', $learningSet->school_id);
    }
}
