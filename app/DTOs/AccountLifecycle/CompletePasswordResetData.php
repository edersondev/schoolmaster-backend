<?php

declare(strict_types=1);

namespace App\DTOs\AccountLifecycle;

final readonly class CompletePasswordResetData
{
    public function __construct(
        public string $token,
        public string $password,
    ) {}
}
