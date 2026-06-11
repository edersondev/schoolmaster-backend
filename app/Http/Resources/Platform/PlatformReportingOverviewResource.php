<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlatformReportingOverviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reporting_health' => $this->resource['reporting_health'],
            'lifecycle_states' => $this->resource['lifecycle_states'],
            'output_availability' => $this->resource['output_availability'],
            'retention_summary' => $this->resource['retention_summary'],
            'failure_summary' => $this->resource['failure_summary'],
        ];
    }
}
