<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupportDiagnosticResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'school_id' => $this->resource['school_id'],
            'school_status' => $this->resource['school_status'],
            'operational_indicators' => $this->resource['operational_indicators'],
            'report_health' => $this->resource['report_health'],
            'lifecycle_summary' => $this->resource['lifecycle_summary'],
            'support_metadata' => $this->resource['support_metadata'],
            'correlation_id' => $this->resource['correlation_id'],
        ];
    }
}
