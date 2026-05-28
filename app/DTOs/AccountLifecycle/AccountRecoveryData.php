<?php

declare(strict_types=1);

namespace App\DTOs\AccountLifecycle;

final readonly class AccountRecoveryData
{
    public function __construct(
        public string $userId,
        public string $action,
        public ?string $reason = null,
    ) {}
}
