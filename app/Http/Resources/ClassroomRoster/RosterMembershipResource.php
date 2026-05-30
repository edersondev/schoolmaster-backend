<?php

declare(strict_types=1);

namespace App\Http\Resources\ClassroomRoster;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RosterMembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'class_section_id' => $this->classSection?->uuid,
            'student_profile_id' => $this->studentProfile?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'status' => $this->status,
            'effective_start_date' => $this->effective_start_date?->toDateString(),
            'effective_end_date' => $this->effective_end_date?->toDateString(),
            'end_reason' => $this->end_reason,
            'created_by_user_id' => $this->creator?->uuid,
            'ended_by_user_id' => $this->ender?->uuid,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
