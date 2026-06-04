<?php

declare(strict_types=1);

namespace App\DTOs\GuardianSelfService;

use App\Models\AcademicPeriod;

final readonly class GuardianAcademicSummaryQuery
{
    public function __construct(
        public GuardianStudentTarget $target,
        public AcademicPeriod $academicPeriod,
    ) {}
}
