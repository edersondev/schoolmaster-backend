<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentLearningSetTimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'academic_period_id' => $this->academicPeriod?->uuid,
            'title' => $this->title,
            'status' => in_array($this->status, ['published', 'active'], true) ? 'published' : $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'entries' => StudentLearningSetEntryResource::collection($this->whenLoaded('entries'))->resolve(),
        ];
    }
}
