<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class StudentTransferValidator
{
    public function assertSourceProfileCanTransfer(StudentProfile $studentProfile): void
    {
        if ($studentProfile->status !== 'active') {
            throw new ConflictException('Only active source student profiles may be transferred.');
        }
    }

    public function assertDestinationPermission(User $actor, ?School $destinationSchool): void
    {
        if ($destinationSchool === null) {
            return;
        }

        if ($destinationSchool->status !== 'active') {
            throw ValidationException::withMessages([
                'destination_school_id' => ['Destination school must be active.'],
            ]);
        }

        if (! $actor->hasSchoolPermission('student_transfers.manage', $destinationSchool->id)) {
            throw new AuthorizationException('The authenticated user lacks permission for the destination school.');
        }
    }

    public function assertDestinationProfile(?StudentProfile $destinationProfile, ?School $destinationSchool): void
    {
        if ($destinationProfile === null) {
            return;
        }

        if ($destinationSchool === null || (int) $destinationProfile->school_id !== (int) $destinationSchool->id) {
            throw ValidationException::withMessages([
                'destination_student_profile_id' => ['Destination student profile must belong to the destination school.'],
            ]);
        }
    }
}
