<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportErrorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->resource['code'] ?? 'report_error',
            'message' => $this->resource['message'] ?? 'Report workflow could not be completed.',
            'details' => $this->resource['details'] ?? [],
        ];
    }
}
