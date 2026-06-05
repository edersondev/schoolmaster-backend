<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class ReportLifecycleActionData
{
    public function __construct(
        public string $reasonCode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self((string) ($data['reason_code'] ?? ''));
    }
}
