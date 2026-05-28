<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use Illuminate\Support\Str;

final class LifecycleTokenService
{
    public function issue(): array
    {
        $plainToken = Str::random(80);

        return [$plainToken, $this->hash($plainToken)];
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function matches(string $plainToken, string $tokenHash): bool
    {
        return hash_equals($tokenHash, $this->hash($plainToken));
    }
}
