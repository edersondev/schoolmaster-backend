<?php

declare(strict_types=1);

namespace App\DTOs\GuardianSelfService;

use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\User;

final readonly class GuardianActorContext
{
    public function __construct(
        public User $user,
        public School $school,
        public Guardian $guardian,
        public GuardianUserLink $link,
    ) {}
}
