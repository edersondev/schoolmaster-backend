<?php

declare(strict_types=1);

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReportRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $generationStatus = $this->generation_status;
        $generationStatus = is_object($generationStatus) && property_exists($generationStatus, 'value') ? $generationStatus->value : $generationStatus;

        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'requested_by_user_id' => $this->requester?->uuid,
            'report_type' => $this->report_type,
            'filter_summary' => $this->filter_summary,
            'output_formats' => $this->output_formats,
            'generation_status' => $generationStatus ?? $this->status,
            'status' => $this->status,
            'source_report_run_id' => $this->sourceReportRun?->uuid,
            'superseded_by_report_run_id' => $this->supersededByReportRun?->uuid,
            'report_definition_id' => $this->reportDefinition?->uuid,
            'report_definition_snapshot_id' => $this->reportDefinitionSnapshot?->uuid,
            'deleted_at' => $this->deleted_at?->toISOString(),
            'failure_reason_code' => $this->failure_reason_code,
            'cancellation_reason_code' => $this->cancellation_reason_code,
            'generated_at' => $this->generated_at?->toISOString(),
            'output_expires_at' => $this->output_expires_at?->toISOString(),
            'outputs_available' => $this->outputs_available,
            'outputs' => $this->whenLoaded('outputs', fn () => $this->outputs->map(fn ($output): array => [
                'id' => $output->uuid,
                'report_run_id' => $this->uuid,
                'format' => $output->format,
                'availability' => is_object($output->availability) && property_exists($output->availability, 'value') ? $output->availability->value : $output->availability,
                'generated_at' => $output->generated_at?->toISOString(),
                'expires_at' => $output->expires_at?->toISOString(),
                'failure_reason_code' => $output->failure_reason_code,
            ])->all()),
        ];
    }
}
