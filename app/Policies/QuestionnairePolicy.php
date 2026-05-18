<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

final class QuestionnairePolicy
{
    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return $user->hasSchoolPermission('questionnaires.view', $questionnaire->school_id);
    }

    public function create(User $user, int $schoolId): bool
    {
        return $user->hasSchoolPermission('questionnaires.manage', $schoolId);
    }
}
