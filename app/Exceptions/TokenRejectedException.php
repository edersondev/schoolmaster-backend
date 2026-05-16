<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class TokenRejectedException extends RuntimeException
{
    public function __construct(
        private readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function reasonCode(): string
    {
        return $this->reasonCode;
    }
}
