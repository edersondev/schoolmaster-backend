<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Throwable;

final class IdentityEmailService
{
    public function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function decide(string $email, ?int $excludedUserId = null): IdentityEmailDecision
    {
        $canonicalEmail = $this->normalize($email);
        $owners = User::query()
            ->withTrashed()
            ->where('identity_email_key', $canonicalEmail)
            ->when($excludedUserId !== null, fn ($query) => $query->whereKeyNot($excludedUserId))
            ->limit(2)
            ->get();

        return new IdentityEmailDecision(
            canonicalEmail: $canonicalEmail,
            owner: $owners->count() === 1 ? $owners->first() : null,
            ambiguous: $owners->count() > 1,
        );
    }

    public function isEmailUniqueViolation(Throwable $exception): bool
    {
        return $exception instanceof UniqueConstraintViolationException
            && $exception->index === 'users_email_unique';
    }
}
