<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentTransfer;
use App\Models\User;

final class StudentTransferPolicy
{
    public function view(User $user, StudentTransfer $transfer): bool
    {
        return $user->hasSchoolPermission('student_profiles.view', $transfer->school_id);
    }
}
