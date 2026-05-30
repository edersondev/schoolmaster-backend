<?php

declare(strict_types=1);

namespace App\Http\Resources\ClassroomRoster;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ClassSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'course' => $this->course_metadata,
            'classroom' => $this->classroom_metadata,
            'section' => $this->section_metadata,
            'group' => $this->group_metadata,
            'status' => $this->status,
            'inactive_reason' => $this->inactive_reason,
            'inactive_effective_at' => $this->inactive_effective_at?->toDateString(),
            'created_by_user_id' => $this->creator?->uuid,
            'updated_by_user_id' => $this->updater?->uuid,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
