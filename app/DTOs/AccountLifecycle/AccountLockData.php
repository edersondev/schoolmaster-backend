<?php

declare(strict_types=1);

namespace App\DTOs\AccountLifecycle;

final readonly class AccountLockData
{
    public function __construct(
        public string $userId,
        public ?string $reason = null,
    ) {}
}
