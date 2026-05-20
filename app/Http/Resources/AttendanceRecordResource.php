<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'student_profile_id' => $this->studentProfile?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'recorded_by_user_id' => $this->recorder?->uuid,
            'attendance_date' => $this->attendance_date?->toDateString(),
            'attendance_status' => $this->attendance_status,
            'status' => $this->status,
        ];
    }
}
