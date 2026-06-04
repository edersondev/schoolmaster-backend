<?php

declare(strict_types=1);

namespace App\Services\Grades;

use App\DTOs\Grades\CreateGradeData;
use App\DTOs\TenantContext;
use App\Models\GradeRecord;
use App\Models\User;
use App\Services\Concerns\AuthorizesTeacherWorkflows;
use App\Services\TeacherWorkflows\AcademicRecordTargetValidator;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GradeRecordService
{
    use AuthorizesTeacherWorkflows;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly TeacherWorkflowListQuery $listQuery,
        private readonly AcademicRecordTargetValidator $targetValidator,
    ) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->listQuery->validate($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'grades.view');

        return GradeRecord::query()
            ->with(['school', 'studentProfile', 'academicPeriod', 'recorder'])
            ->where('school_id', $school->id)
            ->orderByDesc('recorded_at')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateGradeData $data): GradeRecord
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'grades.manage');
        $target = $this->targetValidator->validate($data->studentProfileId, $data->academicPeriodId, $school->id);

        return GradeRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $target['student']->id,
            'academic_period_id' => $target['period']->id,
            'recorded_by_user_id' => $actor->id,
            'original_recorded_by_user_id' => $actor->id,
            'grade_value' => $data->gradeValue,
            'grade_label' => $data->gradeLabel,
            'status' => 'active',
            'recorded_at' => now(),
        ])->load(['school', 'studentProfile', 'academicPeriod', 'recorder']);
    }
}
