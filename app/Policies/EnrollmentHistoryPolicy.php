<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EnrollmentHistory;
use App\Models\User;

final class EnrollmentHistoryPolicy
{
    public function view(User $user, EnrollmentHistory $history): bool
    {
        return $user->hasSchoolPermission('student_profiles.view', $history->school_id);
    }
}
