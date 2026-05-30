<?php

declare(strict_types=1);

namespace App\DTOs\ClassroomRoster;

final readonly class RosterMembershipBatchInput
{
    /**
     * @param  array<int, string>  $recordIds
     */
    public function __construct(
        public string $academicPeriodId,
        public string $effectiveDate,
        public array $recordIds,
        public ?string $reason = null,
    ) {}
}
