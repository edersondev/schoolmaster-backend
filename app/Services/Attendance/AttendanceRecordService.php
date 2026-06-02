<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\DTOs\Attendance\CreateAttendanceData;
use App\DTOs\TenantContext;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\Concerns\AuthorizesTeacherWorkflows;
use App\Services\TeacherWorkflows\AcademicRecordTargetValidator;
use App\Services\TeacherWorkflows\TeacherWorkflowListQuery;
use App\Services\TenantContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AttendanceRecordService
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
        $this->assertTeacherWorkflowPermission($actor, $school, 'attendance.view');

        return AttendanceRecord::query()
            ->with(['school', 'studentProfile', 'academicPeriod', 'recorder'])
            ->where('school_id', $school->id)
            ->orderByDesc('attendance_date')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateAttendanceData $data): AttendanceRecord
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertTeacherWorkflowPermission($actor, $school, 'attendance.manage');
        $target = $this->targetValidator->validate($data->studentProfileId, $data->academicPeriodId, $school->id);

        return AttendanceRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $target['student']->id,
            'academic_period_id' => $target['period']->id,
            'recorded_by_user_id' => $actor->id,
            'original_recorded_by_user_id' => $actor->id,
            'attendance_date' => $data->attendanceDate,
            'attendance_status' => $data->attendanceStatus,
            'status' => 'active',
        ])->load(['school', 'studentProfile', 'academicPeriod', 'recorder']);
    }
}
