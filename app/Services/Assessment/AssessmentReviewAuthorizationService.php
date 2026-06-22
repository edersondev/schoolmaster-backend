<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Exceptions\PermissionDeniedException;
use App\Models\AssessmentResponseAttempt;
use App\Models\User;

final class AssessmentReviewAuthorizationService
{
    public function assertCanReview(User $actor, AssessmentResponseAttempt $attempt): void
    {
        if (! $this->canReview($actor, $attempt)) {
            throw new PermissionDeniedException('The authenticated user lacks same-school assessment review authority.');
        }
    }

    public function canReview(User $actor, AssessmentResponseAttempt $attempt): bool
    {
        $attempt->loadMissing('academicPeriod', 'questionnaire');

        if (! $actor->isActive() || $actor->school_id !== $attempt->school_id) {
            return false;
        }

        if ($attempt->academicPeriod?->status !== 'active') {
            return false;
        }

        if ($actor->hasSchoolPermission('users.manage', $attempt->school_id)) {
            return true;
        }

        return $actor->id === $attempt->questionnaire?->owner_user_id
            && $actor->hasSchoolPermission('questionnaires.manage', $attempt->school_id);
    }
}
