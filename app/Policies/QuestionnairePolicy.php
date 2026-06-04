<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

final class QuestionnairePolicy
{
    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return $this->canManage($user, $questionnaire);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('questionnaires.manage', $schoolId);
    }

    public function update(User $user, Questionnaire $questionnaire): bool
    {
        return $this->canManage($user, $questionnaire);
    }

    public function lifecycle(User $user, Questionnaire $questionnaire): bool
    {
        return $this->canManage($user, $questionnaire);
    }

    private function canManage(User $user, Questionnaire $questionnaire): bool
    {
        if ($user->school_id !== $questionnaire->school_id) {
            return false;
        }

        if ($user->id === $questionnaire->owner_user_id && $user->hasSchoolPermission('questionnaires.manage', $questionnaire->school_id)) {
            return true;
        }

        return $user->hasSchoolPermission('users.manage', $questionnaire->school_id);
    }
}
