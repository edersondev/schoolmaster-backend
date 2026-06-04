<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\TeacherWorkflow\CorrectionInput;
use App\DTOs\TenantContext;
use App\Models\AttendanceRecord;
use App\Models\CorrectionRecord;
use App\Models\GradeRecord;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AcademicRecordCorrectionService
{
    public function __construct(
        private readonly AcademicRecordLifecycleService $records,
        private readonly TeacherWorkflowAuditLogger $audit,
    ) {}

    public function correctGrade(User $actor, TenantContext $context, string $uuid, CorrectionInput $input): GradeRecord
    {
        $record = $this->records->resolveGrade($context, $uuid);
        $this->authorizeCorrection($actor, $record);

        return DB::transaction(function () use ($actor, $input, $record): GradeRecord {
            $originalValue = [
                'grade_value' => $record->original_grade_value ?? $record->grade_value,
                'grade_label' => $record->original_grade_label ?? $record->grade_label,
            ];
            $newValue = [
                'grade_value' => $input->gradeValue,
                'grade_label' => $input->gradeLabel,
            ];

            if ($record->original_grade_value === null) {
                $record->forceFill([
                    'original_grade_value' => $record->grade_value,
                    'original_grade_label' => $record->grade_label,
                ]);
            }

            $record->forceFill([
                'grade_value' => $input->gradeValue,
                'grade_label' => $input->gradeLabel,
            ])->save();

            $this->correction($actor, $record, 'grade', $originalValue, $newValue, $input->reason);
            $this->audit->record('teacher_workflow.correction', 'success', $actor->id, $record->school_id, GradeRecord::class, $record->uuid, [
                'target_record_type' => 'grade',
            ]);

            return $this->records->load($record->refresh());
        });
    }

    public function correctAttendance(User $actor, TenantContext $context, string $uuid, CorrectionInput $input): AttendanceRecord
    {
        $record = $this->records->resolveAttendance($context, $uuid);
        $this->authorizeCorrection($actor, $record);

        return DB::transaction(function () use ($actor, $input, $record): AttendanceRecord {
            $originalValue = ['attendance_status' => $record->original_attendance_status ?? $record->attendance_status];
            $newValue = ['attendance_status' => $input->attendanceStatus];

            if ($record->original_attendance_status === null) {
                $record->forceFill(['original_attendance_status' => $record->attendance_status]);
            }

            $record->forceFill(['attendance_status' => $input->attendanceStatus])->save();

            $this->correction($actor, $record, 'attendance', $originalValue, $newValue, $input->reason);
            $this->audit->record('teacher_workflow.correction', 'success', $actor->id, $record->school_id, AttendanceRecord::class, $record->uuid, [
                'target_record_type' => 'attendance',
            ]);

            return $this->records->load($record->refresh());
        });
    }

    private function authorizeCorrection(User $actor, GradeRecord|AttendanceRecord $record): void
    {
        Gate::forUser($actor)->authorize('correct', $record);

        if ($record->academicPeriod?->status === 'closed' && ! $actor->hasSchoolPermission('users.manage', $record->school_id)) {
            $this->audit->record('teacher_workflow.correction', 'denied', $actor->id, $record->school_id, $record::class, $record->uuid, [
                'denial_category' => 'closed_period',
            ]);

            throw new AuthorizationException('Closed-period corrections require school administrator authority.');
        }
    }

    private function correction(User $actor, GradeRecord|AttendanceRecord $record, string $type, array $originalValue, array $newValue, string $reason): CorrectionRecord
    {
        return CorrectionRecord::query()->create([
            'school_id' => $record->school_id,
            'target_record_type' => $type,
            'target_record_id' => $record->uuid,
            'original_value' => $originalValue,
            'new_value' => $newValue,
            'correction_reason' => $reason,
            'actor_user_id' => $actor->id,
            'academic_period_id' => $record->academic_period_id,
            'student_profile_id' => $record->student_profile_id,
            'student_visible' => true,
            'corrected_at' => now(),
        ]);
    }
}
