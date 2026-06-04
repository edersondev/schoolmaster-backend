<?php

declare(strict_types=1);

namespace App\Services\StudentSelfView;

use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\LearningSet;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentSelfView;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class StudentLearningTimelineService
{
    use AuthorizesStudentSelfView;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly StudentSelfViewListQuery $listQuery,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $school = $this->tenantContext->requireSchool($context);
        $student = $this->activeStudentProfileFor($actor, $school);
        $filters = $this->listQuery->validate($query, academicPeriodRequired: true);
        $period = $this->activeAcademicPeriod((string) $filters['academic_period_id'], $school->id);

        return LearningSet::query()
            ->with(['academicPeriod', 'entries.contentItem', 'entries.questionnaire'])
            ->where('school_id', $school->id)
            ->where('academic_period_id', $period->id)
            ->whereIn('status', ['published', 'active'])
            ->whereHas('assignments', fn ($query) => $query
                ->where('student_profile_id', $student->id)
                ->where('status', 'active'))
            ->orderByDesc('published_at')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    private function activeAcademicPeriod(string $periodUuid, int $schoolId): AcademicPeriod
    {
        $period = AcademicPeriod::query()
            ->where('uuid', $periodUuid)
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->first();

        if ($period === null) {
            throw ValidationException::withMessages([
                'academic_period_id' => ['The academic period must be active and belong to the resolved school.'],
            ]);
        }

        return $period;
    }
}
