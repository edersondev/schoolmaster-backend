<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'user_id' => $this->targetUser?->uuid,
            'school_id' => $this->school?->uuid,
            'scope' => $this->scope,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'delivery_channel' => $this->delivery_channel,
            'delivery_requested_at' => $this->delivery_requested_at?->toIso8601String(),
        ];
    }
}
