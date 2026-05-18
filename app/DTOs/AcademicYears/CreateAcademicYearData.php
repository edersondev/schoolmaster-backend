<?php

declare(strict_types=1);

namespace App\DTOs\AcademicYears;

final readonly class CreateAcademicYearData
{
    public function __construct(
        public string $name,
        public string $startDate,
        public string $endDate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
        );
    }
}
