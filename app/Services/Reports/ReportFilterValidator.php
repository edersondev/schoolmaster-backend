<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\AcademicPeriod;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ReportFilterValidator
{
    private const REPORT_TYPES = ['attendance', 'grades', 'academic_structure', 'school_activity'];

    private const FORMATS = ['pdf', 'csv'];

    /**
     * @param  array<string, mixed>  $payload
     * @return array{report_type: string, filters: array<string, mixed>}
     */
    public function validateRequest(array $payload, int $schoolId): array
    {
        $this->rejectUnknown($payload, ['report_type', 'filters']);
        $this->rejectUnknown((array) ($payload['filters'] ?? []), ['academic_period_id', 'student_profile_id', 'user_id', 'status', 'start_date', 'end_date'], 'filters.');

        $validated = Validator::make($payload, [
            'report_type' => ['required', 'string', Rule::in(self::REPORT_TYPES)],
            'filters' => ['required', 'array'],
            'filters.academic_period_id' => ['required', 'string', 'uuid'],
            'filters.student_profile_id' => ['sometimes', 'string', 'uuid'],
            'filters.user_id' => ['sometimes', 'string', 'uuid'],
            'filters.status' => ['sometimes', 'string'],
            'filters.start_date' => ['sometimes', 'date'],
            'filters.end_date' => ['sometimes', 'date', 'after_or_equal:filters.start_date'],
        ])->validate();

        $filters = $validated['filters'];
        $this->assertAcademicPeriod((string) $filters['academic_period_id'], $schoolId);

        if (isset($filters['student_profile_id'])) {
            $this->assertStudent((string) $filters['student_profile_id'], $schoolId);
        }

        if (isset($filters['user_id'])) {
            $this->assertUser((string) $filters['user_id'], $schoolId);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validateList(array $query): array
    {
        $this->rejectUnknown($query, ['page', 'per_page', 'report_type']);

        return Validator::make($query, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'report_type' => ['sometimes', 'string', Rule::in(self::REPORT_TYPES)],
        ])->validate();
    }

    public function validateFormat(array $query): string
    {
        $this->rejectUnknown($query, ['format']);

        $validated = Validator::make($query, [
            'format' => ['required', 'string', Rule::in(self::FORMATS)],
        ])->validate();

        return $validated['format'];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $allowed
     */
    private function rejectUnknown(array $input, array $allowed, string $prefix = ''): void
    {
        foreach (array_keys($input) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages([$prefix.$field => ['This field is not documented for this request.']]);
            }
        }
    }

    private function assertAcademicPeriod(string $uuid, int $schoolId): void
    {
        if (! AcademicPeriod::query()->where('uuid', $uuid)->where('school_id', $schoolId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['filters.academic_period_id' => ['The academic period must be active and belong to the resolved school.']]);
        }
    }

    private function assertStudent(string $uuid, int $schoolId): void
    {
        if (! StudentProfile::query()->where('uuid', $uuid)->where('school_id', $schoolId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['filters.student_profile_id' => ['The student profile must be active and belong to the resolved school.']]);
        }
    }

    private function assertUser(string $uuid, int $schoolId): void
    {
        if (! User::query()->where('uuid', $uuid)->where('school_id', $schoolId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages(['filters.user_id' => ['The user must be active and belong to the resolved school.']]);
        }
    }
}
