<?php

declare(strict_types=1);

namespace App\DTOs\PlatformSupport;

final readonly class PlatformSupportReasonCode
{
    public function __construct(
        public string $reasonCode,
        public string $correlationId,
        public ?string $purpose = null,
    ) {}

    /**
     * @param  array{reason_code: string, correlation_id: string, purpose?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reasonCode: $data['reason_code'],
            correlationId: $data['correlation_id'],
            purpose: $data['purpose'] ?? null,
        );
    }
}
