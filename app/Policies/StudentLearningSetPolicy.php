<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LearningSet;
use App\Models\StudentProfile;
use App\Models\User;

final class StudentLearningSetPolicy
{
    public function view(User $user, LearningSet $learningSet, StudentProfile $studentProfile): bool
    {
        return $studentProfile->user_id === $user->id
            && $studentProfile->status === 'active'
            && $learningSet->school_id === $studentProfile->school_id
            && in_array($learningSet->status, ['published', 'active'], true)
            && $learningSet->assignments()
                ->where('student_profile_id', $studentProfile->id)
                ->where('status', 'active')
                ->exists();
    }
}
