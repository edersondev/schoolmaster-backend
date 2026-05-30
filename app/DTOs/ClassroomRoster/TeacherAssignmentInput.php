<?php

declare(strict_types=1);

namespace App\DTOs\ClassroomRoster;

final readonly class TeacherAssignmentInput
{
    public function __construct(
        public string $classSectionId,
        public string $teacherUserId,
        public string $academicPeriodId,
        public string $effectiveStartDate,
    ) {}
}
