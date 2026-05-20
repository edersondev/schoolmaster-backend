<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GradeRecord;
use App\Models\StudentProfile;
use App\Models\User;

final class StudentGradeRecordPolicy
{
    public function view(User $user, GradeRecord $record, StudentProfile $studentProfile): bool
    {
        return $studentProfile->user_id === $user->id
            && $studentProfile->status === 'active'
            && $record->student_profile_id === $studentProfile->id
            && $record->school_id === $studentProfile->school_id;
    }
}
