<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportOutputFormatRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_report_can_request_pdf_csv_and_xlsx_outputs(): void
    {
        $school = School::factory()->create();
        $period = $this->period($school);
        $admin = $this->createSchoolAdmin($school, ['reports.request']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_type' => 'attendance',
                'filters' => ['academic_period_id' => $period->uuid],
                'output_formats' => ['pdf', 'csv', 'xlsx'],
            ])
            ->assertAccepted()
            ->assertJsonPath('data.output_formats.2', 'xlsx');
    }

    private function period(School $school): AcademicPeriod
    {
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        return AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
    }
}
