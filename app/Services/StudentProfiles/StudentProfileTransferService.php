<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\StudentProfiles\TransferStudentProfileData;
use App\DTOs\TenantContext;
use App\Models\EnrollmentHistory;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentAdministration;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentProfileTransferService
{
    use AuthorizesStudentAdministration;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly StudentTransferValidator $validator,
    ) {}

    /**
     * @return array{student_profile: StudentProfile, transfer: StudentTransfer, enrollment_history: EnrollmentHistory}
     */
    public function transferForContext(User $actor, TenantContext $context, string $studentProfileId, TransferStudentProfileData $data): array
    {
        $school = $this->tenantContext->requireSchool($context);
        $profile = StudentProfile::query()->where('uuid', $studentProfileId)->where('school_id', $school->id)->first();

        if ($profile === null) {
            throw new ModelNotFoundException;
        }

        return $this->transfer($actor, $school, $profile, $data);
    }

    /**
     * @return array{student_profile: StudentProfile, transfer: StudentTransfer, enrollment_history: EnrollmentHistory}
     */
    public function transfer(User $actor, School $school, StudentProfile $profile, TransferStudentProfileData $data): array
    {
        $this->assertCanTransferStudentProfiles($actor, $school);
        $this->validator->assertSourceProfileCanTransfer($profile);

        $destinationSchool = $this->resolveDestinationSchool($data->destinationSchoolId);
        $destinationProfile = $this->resolveDestinationProfile($data->destinationStudentProfileId);

        $this->validator->assertDestinationPermission($actor, $destinationSchool);
        $this->validator->assertDestinationProfile($destinationProfile, $destinationSchool);

        return DB::transaction(function () use ($actor, $school, $profile, $data, $destinationSchool, $destinationProfile): array {
            $fromStatus = $profile->status;

            $profile->forceFill([
                'status' => 'transferred',
                'status_effective_at' => $data->effectiveAt,
            ])->save();

            $transfer = StudentTransfer::query()->create([
                'school_id' => $school->id,
                'student_profile_id' => $profile->id,
                'destination_school_id' => $destinationSchool?->id,
                'destination_student_profile_id' => $destinationProfile?->id,
                'effective_at' => $data->effectiveAt,
                'reason' => $data->reason,
                'actor_user_id' => $actor->id,
            ]);

            $history = EnrollmentHistory::query()->create([
                'school_id' => $school->id,
                'student_profile_id' => $profile->id,
                'event_type' => 'transferred_out',
                'from_status' => $fromStatus,
                'to_status' => 'transferred',
                'effective_at' => $data->effectiveAt,
                'reason' => $data->reason,
                'actor_user_id' => $actor->id,
                'metadata_summary' => [
                    'destination_school_id' => $destinationSchool?->uuid,
                    'destination_student_profile_id' => $destinationProfile?->uuid,
                ],
            ]);

            return [
                'student_profile' => $profile->refresh()->load(['school', 'user', 'currentAcademicYear', 'guardians.school', 'enrollmentHistories.school', 'enrollmentHistories.studentProfile', 'enrollmentHistories.actor']),
                'transfer' => $transfer->load(['school', 'studentProfile', 'destinationSchool', 'destinationStudentProfile', 'actor']),
                'enrollment_history' => $history->load(['school', 'studentProfile', 'actor']),
            ];
        });
    }

    private function resolveDestinationSchool(?string $schoolId): ?School
    {
        if ($schoolId === null) {
            return null;
        }

        $school = School::query()->where('uuid', $schoolId)->first();

        if ($school === null) {
            throw ValidationException::withMessages([
                'destination_school_id' => ['Destination school must exist.'],
            ]);
        }

        return $school;
    }

    private function resolveDestinationProfile(?string $studentProfileId): ?StudentProfile
    {
        if ($studentProfileId === null) {
            return null;
        }

        $profile = StudentProfile::query()->where('uuid', $studentProfileId)->first();

        if ($profile === null) {
            throw ValidationException::withMessages([
                'destination_student_profile_id' => ['Destination student profile must exist.'],
            ]);
        }

        return $profile;
    }
}
