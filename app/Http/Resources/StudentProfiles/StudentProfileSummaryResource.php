<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'user_id' => $this->user?->uuid,
            'registration_number' => $this->registration_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at?->toDateString(),
            'status_effective_at' => $this->status_effective_at?->toDateString(),
        ];
    }
}
