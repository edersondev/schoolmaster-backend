<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportLifecycleEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'actor_user_id' => $this->actor?->uuid,
            'action' => $this->action,
            'outcome' => $this->outcome,
            'target_type' => $this->target_type,
            'correlation_id' => $this->correlation_id,
            'reason_code' => $this->reason_code,
            'summary' => $this->summary ?? [],
            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
