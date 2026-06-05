<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class ReportDefinitionData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $domain,
        public array $fields,
        public array $filters,
        public array $grouping,
        public array $sorting,
        public array $outputFormats,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['name'] ?? ''),
            isset($data['description']) ? (string) $data['description'] : null,
            (string) ($data['domain'] ?? ''),
            (array) ($data['fields'] ?? []),
            (array) ($data['filters'] ?? []),
            (array) ($data['grouping'] ?? []),
            (array) ($data['sorting'] ?? []),
            (array) ($data['output_formats'] ?? []),
        );
    }
}
