<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\PermissionDeniedException;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;

trait AuthorizesStudentSelfView
{
    private function activeStudentProfileFor(User $actor, School $school): StudentProfile
    {
        $profile = StudentProfile::query()
            ->where('school_id', $school->id)
            ->where('user_id', $actor->id)
            ->where('status', 'active')
            ->first();

        if ($profile === null) {
            throw new PermissionDeniedException('The authenticated user lacks an active student profile in the resolved school.');
        }

        return $profile;
    }
}
