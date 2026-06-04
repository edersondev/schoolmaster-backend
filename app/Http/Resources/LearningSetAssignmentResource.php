<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LearningSetAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'learning_set_id' => $this->learningSet?->uuid,
            'assignment_mode' => $this->assignment_mode ?? 'legacy_direct',
            'class_section_id' => $this->classSection?->uuid,
            'student_profile_id' => ($this->assignment_mode ?? 'legacy_direct') === 'roster' ? null : $this->studentProfile?->uuid,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->toISOString(),
        ];
    }
}
