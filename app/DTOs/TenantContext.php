<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\School;

final readonly class TenantContext
{
    public function __construct(
        public ?School $school,
        public string $source,
        public string $resolutionStatus,
    ) {}

    public function isResolved(): bool
    {
        return $this->resolutionStatus === 'resolved' && $this->school !== null;
    }
}
