<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class RecoverableUserConflictException extends Exception
{
    public function __construct(private readonly string $userUuid)
    {
        parent::__construct('A retained user can be restored.');
    }

    public function userUuid(): string
    {
        return $this->userUuid;
    }
}
