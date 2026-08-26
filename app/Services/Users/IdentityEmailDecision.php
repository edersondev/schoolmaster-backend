<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\User;

final readonly class IdentityEmailDecision
{
    public function __construct(
        public string $canonicalEmail,
        public ?User $owner,
        private bool $ambiguous,
    ) {}

    public function isAvailable(): bool
    {
        return $this->owner === null && ! $this->ambiguous;
    }

    public function isAmbiguous(): bool
    {
        return $this->ambiguous;
    }
}
