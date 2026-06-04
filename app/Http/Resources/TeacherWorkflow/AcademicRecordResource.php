<?php

declare(strict_types=1);

namespace App\Http\Resources\TeacherWorkflow;

use App\Models\AttendanceRecord;
use App\Models\CorrectionRecord;
use App\Models\GradeRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AcademicRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof GradeRecord) {
            return $this->grade($this->resource);
        }

        if ($this->resource instanceof AttendanceRecord) {
            return $this->attendance($this->resource);
        }

        return $this->correction($this->resource);
    }

    private function grade(GradeRecord $record): array
    {
        return [
            'id' => $record->uuid,
            'school_id' => $record->school?->uuid,
            'student_profile_id' => $record->studentProfile?->uuid,
            'academic_period_id' => $record->academicPeriod?->uuid,
            'recorded_by_user_id' => $record->recorder?->uuid,
            'original_recorded_by_user_id' => $record->originalRecorder?->uuid,
            'grade_value' => $record->grade_value,
            'current_value' => $record->grade_value,
            'original_value' => $record->original_grade_value,
            'grade_label' => $record->grade_label,
            'correction_history' => $record->corrections->map(fn (CorrectionRecord $correction): array => $this->correction($correction))->values()->all(),
            'status' => $record->status,
            'recorded_at' => $record->recorded_at?->toISOString(),
        ];
    }

    private function attendance(AttendanceRecord $record): array
    {
        return [
            'id' => $record->uuid,
            'school_id' => $record->school?->uuid,
            'student_profile_id' => $record->studentProfile?->uuid,
            'academic_period_id' => $record->academicPeriod?->uuid,
            'recorded_by_user_id' => $record->recorder?->uuid,
            'original_recorded_by_user_id' => $record->originalRecorder?->uuid,
            'attendance_date' => $record->attendance_date?->toDateString(),
            'attendance_status' => $record->attendance_status,
            'current_value' => $record->attendance_status,
            'original_value' => $record->original_attendance_status,
            'correction_history' => $record->corrections->map(fn (CorrectionRecord $correction): array => $this->correction($correction))->values()->all(),
            'status' => $record->status,
        ];
    }

    private function correction(CorrectionRecord $correction): array
    {
        return [
            'id' => $correction->uuid,
            'school_id' => $correction->school?->uuid,
            'target_record_type' => $correction->target_record_type,
            'target_record_id' => $correction->target_record_id,
            'actor_user_id' => $correction->actor?->uuid,
            'correction_reason' => $correction->correction_reason,
            'corrected_at' => $correction->corrected_at?->toISOString(),
        ];
    }
}
