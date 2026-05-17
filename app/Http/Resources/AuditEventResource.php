<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'event_type' => $this->event_type,
            'actor_user_id' => $this->actor?->uuid,
            'school_id' => $this->school?->uuid,
            'affected_resource_type' => $this->affected_resource_type,
            'affected_resource_id' => $this->affected_resource_id,
            'outcome' => $this->outcome,
            'source_ip' => $this->source_ip,
            'tenant_safe_metadata' => $this->tenant_safe_metadata ?? [],
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
