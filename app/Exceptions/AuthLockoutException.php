<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AuthLockoutException extends RuntimeException
{
    public function __construct(
        private readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many failed login attempts. Try again later.');
    }

    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
