<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TokenRejectedException;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Str;

final class AuthTokenLifecycleService
{
    public function issue(User $user): array
    {
        $plainToken = Str::random(80);
        $expiresAt = now()->addHours(8);

        $token = AuthToken::query()->create([
            'user_id' => $user->getKey(),
            'school_id' => $user->school_id,
            'name' => 'api',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        return [$plainToken, $expiresAt, $token];
    }

    public function resolve(?string $bearerToken): AuthToken
    {
        if ($bearerToken === null || $bearerToken === '') {
            throw new TokenRejectedException('unauthorized', 'Authentication is missing or invalid.');
        }

        /** @var AuthToken|null $token */
        $token = AuthToken::query()
            ->with(['user.school', 'school'])
            ->where('token_hash', hash('sha256', $bearerToken))
            ->first();

        if ($token === null || $token->revoked_at !== null) {
            throw new TokenRejectedException('token_revoked', 'Bearer token has been revoked.');
        }

        if ($token->expires_at->isPast()) {
            throw new TokenRejectedException('token_expired', 'Bearer token has expired.');
        }

        if ($token->user->status !== 'active') {
            throw new TokenRejectedException('inactive_user', 'Inactive users cannot access protected workflows.');
        }

        if ($token->user->school !== null && $token->user->school->status !== 'active') {
            throw new TokenRejectedException('inactive_school', 'Inactive schools cannot access protected workflows.');
        }

        return $token;
    }

    public function revoke(AuthToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }
}
