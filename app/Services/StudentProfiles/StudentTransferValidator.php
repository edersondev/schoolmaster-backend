<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class StudentTransferValidator
{
    public function assertSourceProfileCanTransfer(StudentProfile $studentProfile): void
    {
        if ($studentProfile->status !== 'active') {
            throw new ConflictException('Only active source student profiles may be transferred.');
        }
    }

    public function authorizedDestinationSchoolIds(User $actor): Collection
    {
        return $actor->roles()
            ->where('roles.status', 'active')
            ->where('roles.scope', 'school')
            ->whereHas('permissions', fn ($query) => $query
                ->where('code', 'student_transfers.manage')
                ->where('scope', 'school')
                ->where('permissions.status', 'active'))
            ->pluck('roles.school_id')
            ->filter(static fn ($schoolId): bool => $schoolId !== null)
            ->values();
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

    public function assertDestinationProfileContext(?string $destinationSchoolId, ?string $destinationStudentProfileId): void
    {
        if ($destinationStudentProfileId !== null && $destinationSchoolId === null) {
            throw ValidationException::withMessages([
                'destination_school_id' => ['Destination school is required when linking a destination student profile.'],
            ]);
        }
    }

    public function assertDestinationSchoolAuthorized(?string $destinationSchoolId, Collection $authorizedSchoolIds): void
    {
        if ($destinationSchoolId === null) {
            return;
        }

        $school = School::query()
            ->where('uuid', $destinationSchoolId)
            ->whereIn('id', $authorizedSchoolIds->all())
            ->where('status', 'active')
            ->first();

        if ($school === null) {
            throw new AuthorizationException('The authenticated user lacks permission for the destination school.');
        }
    }
}
