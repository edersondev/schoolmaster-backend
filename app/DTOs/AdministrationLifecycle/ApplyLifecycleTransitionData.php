<?php

declare(strict_types=1);

namespace App\DTOs\AdministrationLifecycle;

final readonly class ApplyLifecycleTransitionData
{
    public function __construct(
        public string $effectiveAt,
        public string $reason,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            effectiveAt: (string) $data['effective_at'],
            reason: (string) $data['reason'],
        );
    }
}
