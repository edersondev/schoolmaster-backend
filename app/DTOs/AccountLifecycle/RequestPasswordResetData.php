<?php

declare(strict_types=1);

namespace App\DTOs\AccountLifecycle;

final readonly class RequestPasswordResetData
{
    /**
     * @param  array<string, mixed>  $deliveryMetadata
     */
    public function __construct(
        public string $email,
        public ?string $schoolId = null,
        public array $deliveryMetadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower($data['email']),
            schoolId: $data['school_id'] ?? null,
            deliveryMetadata: $data['delivery_metadata'] ?? [],
        );
    }
}
