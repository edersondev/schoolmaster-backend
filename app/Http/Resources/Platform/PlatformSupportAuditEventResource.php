<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\PlatformSupportAuditEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlatformSupportAuditEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PlatformSupportAuditEvent $event */
        $event = $this->resource;

        return [
            'id' => $event->uuid,
            'actor_id' => $event->actor?->uuid,
            'action' => $event->action,
            'outcome' => $event->outcome,
            'target_school_id' => $event->school?->uuid,
            'target_type' => $event->target_type,
            'target_id' => $event->target_id,
            'correlation_id' => $event->correlation_id,
            'reason_code' => $event->reason_code,
            'occurred_at' => $event->occurred_at?->toJSON(),
            'metadata' => $event->metadata ?? (object) [],
        ];
    }
}
