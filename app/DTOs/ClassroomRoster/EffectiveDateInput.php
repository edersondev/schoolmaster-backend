<?php

declare(strict_types=1);

namespace App\DTOs\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\School;
use Carbon\CarbonImmutable;

final readonly class EffectiveDateInput
{
    public function __construct(
        public School $school,
        public AcademicPeriod $academicPeriod,
        public CarbonImmutable $effectiveDate,
        public string $field,
    ) {}
}
