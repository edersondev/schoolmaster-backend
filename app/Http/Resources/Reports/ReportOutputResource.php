<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportOutputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $availability = $this->availability;

        return [
            'id' => $this->uuid,
            'report_run_id' => $this->reportRun?->uuid,
            'format' => $this->format,
            'availability' => $availability instanceof BackedEnum ? $availability->value : $availability,
            'generated_at' => $this->generated_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'failure_reason_code' => $this->failure_reason_code,
        ];
    }
}
