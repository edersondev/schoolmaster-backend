<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlatformSchoolSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'school_id' => $this->resource['school_id'],
            'name' => $this->resource['name'],
            'status' => $this->resource['status'],
            'protected_counts' => $this->resource['protected_counts'],
            'report_health' => $this->resource['report_health'],
            'lifecycle_summary' => $this->resource['lifecycle_summary'],
            'support_diagnostics' => $this->resource['support_diagnostics'],
        ];
    }
}
