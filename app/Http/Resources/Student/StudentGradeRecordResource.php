<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentGradeRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'student_profile_id' => $this->studentProfile?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'recorded_by_user_id' => $this->recorder?->uuid,
            'grade_value' => $this->grade_value,
            'grade_label' => $this->grade_label,
            'status' => $this->status,
            'recorded_at' => $this->recorded_at?->toISOString(),
        ];
    }
}
