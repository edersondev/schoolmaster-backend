<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentResponseAttempt;
use App\Models\User;

final class AssessmentPolicy
{
    public function viewStudent(User $user, AssessmentResponseAttempt $attempt): bool
    {
        return $user->school_id === $attempt->school_id
            && $user->studentProfile !== null
            && $user->studentProfile->id === $attempt->student_profile_id;
    }

    public function review(User $user, AssessmentResponseAttempt $attempt): bool
    {
        return $this->hasReviewAuthority($user, $attempt);
    }

    public function grade(User $user, AssessmentResponseAttempt $attempt): bool
    {
        return $this->hasReviewAuthority($user, $attempt);
    }

    public function download(User $user, AssessmentFileAttachment $attachment): bool
    {
        if ($attachment->scan_status !== 'clean') {
            return false;
        }

        $attempt = $attachment->answer?->responseAttempt;

        return $attempt instanceof AssessmentResponseAttempt
            && $this->hasReviewAuthority($user, $attempt);
    }

    private function hasReviewAuthority(User $user, AssessmentResponseAttempt $attempt): bool
    {
        if ($user->school_id !== $attempt->school_id) {
            return false;
        }

        if ($user->hasSchoolPermission('users.manage', $attempt->school_id)) {
            return true;
        }

        return $user->hasSchoolPermission('questionnaires.manage', $attempt->school_id);
    }
}
