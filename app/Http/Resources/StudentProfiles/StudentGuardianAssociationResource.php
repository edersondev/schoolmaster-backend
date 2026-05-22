<?php

declare(strict_types=1);

namespace App\Http\Resources\StudentProfiles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentGuardianAssociationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'full_name' => $this->full_name,
            'relationship_type' => $this->pivot?->relationship_type ?? $this->relationship_type,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
        ];
    }
}
