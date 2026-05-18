<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningSet;
use App\Models\User;

final class LearningSetPolicy
{
    public function view(User $user, LearningSet $learningSet): bool
    {
        return $user->hasSchoolPermission('learning_sets.view', $learningSet->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('learning_sets.manage', $schoolId);
    }
}
