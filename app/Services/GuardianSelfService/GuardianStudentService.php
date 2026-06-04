<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianActorContext;
use App\Models\StudentProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GuardianStudentService
{
    public function __construct(private readonly GuardianVisibilityService $visibility) {}

    public function list(GuardianActorContext $actor, int $perPage = 25): LengthAwarePaginator
    {
        return StudentProfile::query()
            ->with(['school', 'currentAcademicYear'])
            ->select('student_profiles.*', 'guardian_student_profile.relationship_type as guardian_relationship_label')
            ->join('guardian_student_profile', 'guardian_student_profile.student_profile_id', '=', 'student_profiles.id')
            ->where('student_profiles.school_id', $actor->school->id)
            ->where('student_profiles.status', 'active')
            ->where('guardian_student_profile.school_id', $actor->school->id)
            ->where('guardian_student_profile.guardian_id', $actor->guardian->id)
            ->where('guardian_student_profile.status', 'active')
            ->orderBy('student_profiles.last_name')
            ->orderBy('student_profiles.first_name')
            ->paginate($perPage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summarizePage(LengthAwarePaginator $paginator): array
    {
        return array_map(
            fn (StudentProfile $student): array => $this->visibility->studentSummary(
                $student,
                (string) ($student->guardian_relationship_label ?: 'guardian'),
            ),
            $paginator->items(),
        );
    }
}
