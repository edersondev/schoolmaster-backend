<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\Reports\ReportFilterValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReportFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_filter_validator_rejects_unsupported_report_type(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);

        $this->expectException(ValidationException::class);

        (new ReportFilterValidator)->validateRequest([
            'report_type' => 'custom',
            'filters' => ['academic_period_id' => $period->uuid],
        ], $school->id);
    }
}
