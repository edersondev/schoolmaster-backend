<?php

declare(strict_types=1);

namespace App\DTOs\Grades;

final readonly class CreateGradeData
{
    public function __construct(
        public string $studentProfileId,
        public string $academicPeriodId,
        public float $gradeValue,
        public ?string $gradeLabel,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            studentProfileId: $data['student_profile_id'],
            academicPeriodId: $data['academic_period_id'],
            gradeValue: (float) $data['grade_value'],
            gradeLabel: $data['grade_label'] ?? null,
        );
    }
}
