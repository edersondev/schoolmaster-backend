<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\Models\AuthToken;
use App\Models\User;

final class BearerTokenRevocationService
{
    public function revokeAllForUser(User $user): int
    {
        return AuthToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
