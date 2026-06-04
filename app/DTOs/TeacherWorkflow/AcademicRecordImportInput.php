<?php

declare(strict_types=1);

namespace App\DTOs\TeacherWorkflow;

final readonly class AcademicRecordImportInput
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public string $type,
        public array $rows,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function grades(array $data): self
    {
        return new self('grade', $data['rows'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function attendance(array $data): self
    {
        return new self('attendance', $data['rows'] ?? []);
    }
}
