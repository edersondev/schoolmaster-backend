<?php

declare(strict_types=1);

namespace App\Http\Resources\AdministrationLifecycle;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BulkLifecycleOutcomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'resource_type' => $this->resource_type,
            'action' => $this->action,
            'affected_count' => count($this->results),
            'results' => LifecycleOutcomeResource::collection($this->results)->resolve(),
        ];
    }
}
