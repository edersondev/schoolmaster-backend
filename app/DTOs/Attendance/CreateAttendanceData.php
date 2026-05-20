<?php

declare(strict_types=1);

namespace App\DTOs\Attendance;

final readonly class CreateAttendanceData
{
    public function __construct(
        public string $studentProfileId,
        public string $academicPeriodId,
        public string $attendanceDate,
        public string $attendanceStatus,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            studentProfileId: $data['student_profile_id'],
            academicPeriodId: $data['academic_period_id'],
            attendanceDate: $data['attendance_date'],
            attendanceStatus: $data['attendance_status'],
        );
    }
}
