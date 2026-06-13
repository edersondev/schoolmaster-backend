<?php

declare(strict_types=1);

namespace App\DTOs\Assessment;

use App\Models\School;
use App\Models\User;

final readonly class AssessmentActorContext
{
    public function __construct(
        public User $actor,
        public School $school,
        public string $authority,
        public string $correlationId,
    ) {}
}
