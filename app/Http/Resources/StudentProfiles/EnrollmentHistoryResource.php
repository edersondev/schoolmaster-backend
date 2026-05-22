<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EnrollmentHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'student_profile_id' => $this->studentProfile?->uuid,
            'event_type' => $this->event_type,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'effective_at' => $this->effective_at?->toDateString(),
            'reason' => $this->reason,
            'actor_user_id' => $this->actor?->uuid,
            'metadata_summary' => $this->metadata_summary ?? (object) [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
