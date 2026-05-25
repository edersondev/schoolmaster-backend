<?php

declare(strict_types=1);

namespace App\DTOs\StudentProfiles;

final readonly class UpdateStudentProfileStatusData
{
    public function __construct(
        public string $status,
        public string $effectiveAt,
        public string $reason,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            effectiveAt: $data['effective_at'],
            reason: $data['reason'],
        );
    }
}
