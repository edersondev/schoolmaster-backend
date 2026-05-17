<?php

declare(strict_types=1);

namespace App\DTOs\AcademicPeriods;

final readonly class CreateAcademicPeriodData
{
    public function __construct(
        public string $academicYearId,
        public string $name,
        public int $sequence,
        public string $startDate,
        public string $endDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            academicYearId: $data['academic_year_id'],
            name: $data['name'],
            sequence: (int) $data['sequence'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
        );
    }
}
