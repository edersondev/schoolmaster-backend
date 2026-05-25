<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use App\DTOs\StudentProfiles\UpdateStudentProfileStatusData;
use App\DTOs\TenantContext;
use App\Models\EnrollmentHistory;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentAdministration;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class StudentProfileLifecycleService
{
    use AuthorizesStudentAdministration;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly StudentProfileLifecycleRules $rules,
    ) {}

    /**
     * @return array{student_profile: StudentProfile, enrollment_history: EnrollmentHistory}
     */
    public function updateStatus(User $actor, TenantContext $context, string $studentProfileId, UpdateStudentProfileStatusData $data): array
    {
        $school = $this->tenantContext->requireSchool($context);

        $profile = StudentProfile::query()
            ->where('uuid', $studentProfileId)
            ->where('school_id', $school->id)
            ->first();

        if ($profile === null) {
            throw new ModelNotFoundException;
        }

        $this->assertCanManageStudentProfiles($actor, $school);
        $this->rules->assertNonTransferTransition($profile, $data->status);

        return DB::transaction(function () use ($actor, $school, $profile, $data): array {
            $fromStatus = $profile->status;

            $profile->forceFill([
                'status' => $data->status,
                'status_effective_at' => $data->effectiveAt,
            ])->save();

            $history = EnrollmentHistory::query()->create([
                'school_id' => $school->id,
                'student_profile_id' => $profile->id,
                'event_type' => $this->rules->eventTypeFor($data->status),
                'from_status' => $fromStatus,
                'to_status' => $data->status,
                'effective_at' => $data->effectiveAt,
                'reason' => $data->reason,
                'actor_user_id' => $actor->id,
                'metadata_summary' => [],
            ]);

            return [
                'student_profile' => $profile->refresh()->load(['school', 'user', 'currentAcademicYear', 'guardians.school', 'enrollmentHistories.school', 'enrollmentHistories.studentProfile', 'enrollmentHistories.actor']),
                'enrollment_history' => $history->load(['school', 'studentProfile', 'actor']),
            ];
        });
    }
}
