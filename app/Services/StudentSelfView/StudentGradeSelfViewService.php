<?php

declare(strict_types=1);

namespace App\Services\StudentSelfView;

use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\GradeRecord;
use App\Models\User;
use App\Services\Concerns\AuthorizesStudentSelfView;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class StudentGradeSelfViewService
{
    use AuthorizesStudentSelfView;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly StudentSelfViewListQuery $listQuery,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query, academicPeriodRequired: false);
        $school = $this->tenantContext->requireSchool($context);
        $student = $this->activeStudentProfileFor($actor, $school);

        $records = GradeRecord::query()
            ->with(['school', 'studentProfile', 'academicPeriod', 'recorder'])
            ->where('school_id', $school->id)
            ->where('student_profile_id', $student->id)
            ->where('status', 'active');

        if (isset($filters['academic_period_id'])) {
            $records->where('academic_period_id', $this->activeAcademicPeriod((string) $filters['academic_period_id'], $school->id)->id);
        }

        return $records->orderByDesc('recorded_at')->paginate((int) ($filters['per_page'] ?? 25));
    }

    private function activeAcademicPeriod(string $periodUuid, int $schoolId): AcademicPeriod
    {
        $period = AcademicPeriod::query()->where('uuid', $periodUuid)->where('school_id', $schoolId)->where('status', 'active')->first();

        if ($period === null) {
            throw ValidationException::withMessages(['academic_period_id' => ['The academic period must be active and belong to the resolved school.']]);
        }

        return $period;
    }
}
