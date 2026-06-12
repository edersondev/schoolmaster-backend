<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\TargetSchoolSupportOptIn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SchoolSupportOptInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TargetSchoolSupportOptIn $optIn */
        $optIn = $this->resource;

        return [
            'id' => $optIn->uuid,
            'school_id' => $optIn->school?->uuid,
            'state' => $optIn->state,
            'reason_code' => $optIn->reason_code,
            'purpose' => $optIn->purpose,
            'correlation_id' => $optIn->correlation_id,
            'approved_by_user_id' => $optIn->approvedBy?->uuid,
            'approved_at' => $optIn->approved_at?->toJSON(),
            'expires_at' => $optIn->expires_at?->toJSON(),
            'revoked_at' => $optIn->revoked_at?->toJSON(),
            'created_at' => $optIn->created_at?->toJSON(),
            'updated_at' => $optIn->updated_at?->toJSON(),
        ];
    }
}
