<?php

declare(strict_types=1);

namespace App\DTOs\LearningSets;

final readonly class CreateLearningSetData
{
    public function __construct(
        public string $academicPeriodId,
        public string $title,
        public array $entries,
        public array $studentProfileIds,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            academicPeriodId: $data['academic_period_id'],
            title: $data['title'],
            entries: $data['entries'],
            studentProfileIds: $data['student_profile_ids'],
        );
    }
}
