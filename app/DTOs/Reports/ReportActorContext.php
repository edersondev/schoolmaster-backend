<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

use App\Models\School;
use App\Models\User;

final readonly class ReportActorContext
{
    public function __construct(
        public User $actor,
        public School $school,
        public string $correlationId,
    ) {}
}
