<?php

declare(strict_types=1);

namespace App\DTOs\GuardianSelfService;

use App\Models\StudentProfile;

final readonly class GuardianStudentTarget
{
    public function __construct(
        public GuardianActorContext $actor,
        public StudentProfile $student,
        public string $relationshipLabel,
    ) {}
}
