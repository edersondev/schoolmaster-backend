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
            'student_profile_id' => $this->studentProfile?->uuid,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->toISOString(),
        ];
    }
}
