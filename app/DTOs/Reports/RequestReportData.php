<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class RequestReportData
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public string $reportType,
        public array $filters,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            reportType: (string) $payload['report_type'],
            filters: $payload['filters'],
        );
    }
}
