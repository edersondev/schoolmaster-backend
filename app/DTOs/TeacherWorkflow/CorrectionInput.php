<?php

declare(strict_types=1);

namespace App\DTOs\TeacherWorkflow;

final readonly class CorrectionInput
{
    public function __construct(
        public string $recordType,
        public string $reason,
        public ?float $gradeValue = null,
        public ?string $gradeLabel = null,
        public ?string $attendanceStatus = null,
    ) {}

    public static function grade(array $data): self
    {
        return new self(
            recordType: 'grade',
            reason: $data['correction_reason'],
            gradeValue: (float) $data['grade_value'],
            gradeLabel: $data['grade_label'] ?? null,
        );
    }

    public static function attendance(array $data): self
    {
        return new self(
            recordType: 'attendance',
            reason: $data['correction_reason'],
            attendanceStatus: $data['attendance_status'],
        );
    }
}
