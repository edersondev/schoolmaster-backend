<?php

declare(strict_types=1);

namespace App\DTOs\PlatformSupport;

use App\Models\User;

final readonly class PlatformSupportActorContext
{
    public function __construct(
        public User $actor,
        public bool $isPlatformActor,
    ) {}

    public static function fromUser(User $actor): self
    {
        return new self(
            actor: $actor,
            isPlatformActor: $actor->isPlatformUser(),
        );
    }
}
