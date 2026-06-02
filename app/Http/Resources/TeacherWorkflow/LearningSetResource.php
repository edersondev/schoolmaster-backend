<?php

declare(strict_types=1);

namespace App\Http\Resources\TeacherWorkflow;

use App\Http\Resources\LearningSetAssignmentResource;
use App\Http\Resources\LearningSetEntryResource;
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
            'description' => $this->description,
            'due_at' => $this->due_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'status' => $this->status,
            'entries' => LearningSetEntryResource::collection($this->entries)->resolve(),
            'assignments' => LearningSetAssignmentResource::collection($this->assignments)->resolve(),
        ];
    }
}
