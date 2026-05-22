<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\StudentProfiles\CreateStudentProfileData;
use App\Exceptions\ConflictException;
use App\Models\AcademicYear;
use App\Models\EnrollmentHistory;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentAdministration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentProfileCreationService
{
    use AuthorizesStudentAdministration;

    public function __construct(private readonly GuardianAssociationValidator $guardians) {}

    public function create(User $actor, School $school, CreateStudentProfileData $data): StudentProfile
    {
        $this->assertCanManageStudentProfiles($actor, $school);
        $this->assertRegistrationIsUnique($school, $data->registrationNumber);
        $user = $this->resolveUser($school, $data->userId);
        $academicYear = $this->resolveAcademicYear($school, $data->currentAcademicYearId);
        $guardians = $this->guardians->activeSameSchoolGuardians($data->guardianAssociations, $school);

        return DB::transaction(function () use ($actor, $school, $data, $user, $academicYear, $guardians): StudentProfile {
            $profile = StudentProfile::query()->create([
                'school_id' => $school->id,
                'user_id' => $user?->id,
                'registration_number' => $data->registrationNumber,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'date_of_birth' => $data->dateOfBirth,
                'contact_email' => $data->contactEmail,
                'contact_phone' => $data->contactPhone,
                'current_academic_year_id' => $academicYear?->id,
                'status' => $data->status,
                'enrolled_at' => $data->enrolledAt,
                'status_effective_at' => $data->enrolledAt,
            ]);

            foreach ($data->guardianAssociations as $association) {
                $guardian = $guardians->firstWhere('uuid', $association['guardian_id']);
                $profile->guardians()->attach($guardian->id, [
                    'school_id' => $school->id,
                    'relationship_type' => $association['relationship_type'],
                    'status' => 'active',
                ]);
            }

            EnrollmentHistory::query()->create([
                'school_id' => $school->id,
                'student_profile_id' => $profile->id,
                'event_type' => 'created',
                'from_status' => null,
                'to_status' => $profile->status,
                'effective_at' => $data->enrolledAt,
                'reason' => 'Student profile created.',
                'actor_user_id' => $actor->id,
                'metadata_summary' => ['registration_number' => $data->registrationNumber],
            ]);

            return $profile->load(['school', 'user', 'currentAcademicYear', 'guardians.school', 'enrollmentHistories.school', 'enrollmentHistories.studentProfile', 'enrollmentHistories.actor']);
        });
    }

    private function assertRegistrationIsUnique(School $school, string $registrationNumber): void
    {
        if (StudentProfile::query()->where('school_id', $school->id)->where('registration_number', $registrationNumber)->exists()) {
            throw new ConflictException('Student profile registration number already exists in the resolved school.');
        }
    }

    private function resolveUser(School $school, ?string $userId): ?User
    {
        if ($userId === null) {
            return null;
        }

        $user = User::query()->where('uuid', $userId)->where('school_id', $school->id)->where('status', 'active')->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'user_id' => ['User must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $user;
    }

    private function resolveAcademicYear(School $school, ?string $academicYearId): ?AcademicYear
    {
        if ($academicYearId === null) {
            return null;
        }

        $academicYear = AcademicYear::query()->where('uuid', $academicYearId)->where('school_id', $school->id)->where('status', 'active')->first();

        if ($academicYear === null) {
            throw ValidationException::withMessages([
                'current_academic_year_id' => ['Academic year must exist, be active, and belong to the resolved school.'],
            ]);
        }

        return $academicYear;
    }
}
