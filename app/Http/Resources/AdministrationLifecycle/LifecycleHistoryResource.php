<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LifecycleHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_uuid,
            'operation' => $this->operation,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'effective_at' => $this->effective_at?->toDateString(),
            'reason' => $this->reason,
            'actor_user_id' => $this->actor?->uuid,
            'metadata_summary' => $this->metadata_summary ?? (object) [],
            'created_at' => $this->created_at?->toJSON(),
        ];
    }
}
