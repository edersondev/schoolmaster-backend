<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'requested_by_user_id' => $this->requester?->uuid,
            'report_type' => $this->report_type,
            'filter_summary' => $this->filter_summary,
            'output_formats' => $this->output_formats,
            'status' => $this->status,
            'generated_at' => $this->generated_at?->toISOString(),
            'output_expires_at' => $this->output_expires_at?->toISOString(),
            'outputs_available' => $this->outputs_available,
        ];
    }
}
