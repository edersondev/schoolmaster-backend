<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\GuardianUserLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin GuardianUserLink
 */
final class GuardianUserLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'guardian_id' => $this->guardian?->uuid,
            'user_id' => $this->user?->uuid,
            'status' => $this->status,
            'created_at' => $this->created_at?->toJSON(),
            'deactivated_at' => $this->deactivated_at?->toJSON(),
            'deactivation_reason' => $this->deactivation_reason,
        ];
    }
}
