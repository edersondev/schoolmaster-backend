<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class ReportRequestData
{
    public function __construct(
        public ?string $reportType,
        public ?string $reportDefinitionId,
        public array $filters,
        public array $outputFormats,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['report_type']) ? (string) $data['report_type'] : null,
            isset($data['report_definition_id']) ? (string) $data['report_definition_id'] : null,
            (array) ($data['filters'] ?? []),
            (array) ($data['output_formats'] ?? ['pdf', 'csv']),
        );
    }
}
