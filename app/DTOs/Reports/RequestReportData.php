<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class RequestReportData
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public ?string $reportType,
        public array $filters,
        public ?string $reportDefinitionId = null,
        public array $outputFormats = ['pdf', 'csv'],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            reportType: isset($payload['report_type']) ? (string) $payload['report_type'] : null,
            filters: $payload['filters'],
            reportDefinitionId: isset($payload['report_definition_id']) ? (string) $payload['report_definition_id'] : null,
            outputFormats: (array) ($payload['output_formats'] ?? ['pdf', 'csv']),
        );
    }
}
