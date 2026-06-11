<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\SupportAccessDecision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupportAccessDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SupportAccessDecision $decision */
        $decision = $this->resource;

        return [
            'id' => $decision->uuid,
            'actor_id' => $decision->actor?->uuid,
            'school_id' => $decision->school?->uuid,
            'reason_code' => $decision->reason_code,
            'purpose' => $decision->purpose,
            'correlation_id' => $decision->correlation_id,
            'state' => $decision->state,
            'support_opt_in_state' => $decision->support_opt_in_state,
            'platform_approval_state' => $decision->platform_approval_state,
            'approved_at' => $decision->approved_at?->toJSON(),
            'expires_at' => $decision->expires_at?->toJSON(),
            'revoked_at' => $decision->revoked_at?->toJSON(),
            'created_at' => $decision->created_at?->toJSON(),
            'updated_at' => $decision->updated_at?->toJSON(),
        ];
    }
}
