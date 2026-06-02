<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\ClassroomRoster\EffectiveDateInput;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class EffectiveDateValidator
{
    public function assertUsable(EffectiveDateInput $input): void
    {
        $errors = [];

        if ($input->effectiveDate->isAfter($this->todayForSchool($input))) {
            $errors[$input->field][] = 'The effective date must be today or a past date in the school timezone.';
        }

        if (! $this->isWithinAcademicPeriod($input)) {
            $errors[$input->field][] = 'The effective date must fall within the selected academic period.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function assertEndingDateNotBeforeStart(CarbonImmutable $endingDate, CarbonImmutable $startDate, string $field): void
    {
        if ($endingDate->isBefore($startDate)) {
            throw ValidationException::withMessages([
                $field => ['The lifecycle ending date must be on or after the effective start date.'],
            ]);
        }
    }

    public function todayForSchool(EffectiveDateInput $input): CarbonImmutable
    {
        $timezone = (string) ($input->school->getAttribute('timezone') ?: config('app.timezone', 'UTC'));

        return CarbonImmutable::now($timezone)->startOfDay();
    }

    private function isWithinAcademicPeriod(EffectiveDateInput $input): bool
    {
        $start = CarbonImmutable::parse($input->academicPeriod->start_date)->startOfDay();
        $end = CarbonImmutable::parse($input->academicPeriod->end_date)->startOfDay();

        return $input->effectiveDate->betweenIncluded($start, $end);
    }
}
