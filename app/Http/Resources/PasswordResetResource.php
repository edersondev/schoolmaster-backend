<?php

declare(strict_types=1);

namespace App\Http\Resources;

final class PasswordResetResource
{
    public static function accepted(): array
    {
        return ['accepted' => true];
    }
}
