<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthLockoutException;
use App\Models\LoginAttempt;
use Carbon\CarbonImmutable;

final class LoginAttemptControlService
{
    private const MAX_FAILURES = 5;

    private const WINDOW_MINUTES = 15;

    public function assertNotLocked(string $email, string $ip): void
    {
        $lockedUntil = $this->lockedUntil($email, $ip);

        if ($lockedUntil !== null && $lockedUntil->isFuture()) {
            throw new AuthLockoutException(now()->diffInSeconds($lockedUntil, false));
        }
    }

    public function recordFailure(string $email, string $ip): void
    {
        $this->increment('email', strtolower($email));
        $this->increment('ip', $ip);
    }

    public function clear(string $email, string $ip): void
    {
        LoginAttempt::query()
            ->where(function ($query) use ($email, $ip): void {
                $query->where(fn ($query) => $query->where('attempt_key_type', 'email')->where('attempt_key', strtolower($email)))
                    ->orWhere(fn ($query) => $query->where('attempt_key_type', 'ip')->where('attempt_key', $ip));
            })
            ->delete();
    }

    private function increment(string $type, string $key): void
    {
        $attempt = LoginAttempt::query()->firstOrNew([
            'attempt_key_type' => $type,
            'attempt_key' => $key,
        ]);

        $now = now();
        $windowStartedAt = $attempt->window_started_at;

        if ($windowStartedAt === null || $windowStartedAt->lt($now->copy()->subMinutes(self::WINDOW_MINUTES))) {
            $attempt->failed_attempt_count = 0;
            $attempt->window_started_at = $now;
            $attempt->locked_until = null;
        }

        $attempt->failed_attempt_count++;

        if ($attempt->failed_attempt_count >= self::MAX_FAILURES) {
            $attempt->locked_until = $now->copy()->addMinutes(self::WINDOW_MINUTES);
        }

        $attempt->save();
    }

    private function lockedUntil(string $email, string $ip): ?CarbonImmutable
    {
        $attempt = LoginAttempt::query()
            ->whereNotNull('locked_until')
            ->where(function ($query) use ($email, $ip): void {
                $query->where(fn ($query) => $query->where('attempt_key_type', 'email')->where('attempt_key', strtolower($email)))
                    ->orWhere(fn ($query) => $query->where('attempt_key_type', 'ip')->where('attempt_key', $ip));
            })
            ->orderByDesc('locked_until')
            ->first();

        return $attempt?->locked_until?->toImmutable();
    }
}
