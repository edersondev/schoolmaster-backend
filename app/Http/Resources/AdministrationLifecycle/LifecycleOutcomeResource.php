<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LifecycleOutcomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_uuid,
            'action' => $this->action,
            'status' => $this->status,
            'lifecycle_history' => new LifecycleHistoryResource($this->history),
        ];
    }
}
