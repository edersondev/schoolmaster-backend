<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LearningSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'owner_user_id' => $this->owner?->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'title' => $this->title,
            'published_at' => $this->published_at?->toISOString(),
            'status' => $this->status,
            'entries' => LearningSetEntryResource::collection($this->whenLoaded('entries'))->resolve(),
            'assignments' => LearningSetAssignmentResource::collection($this->whenLoaded('assignments'))->resolve(),
        ];
    }
}
