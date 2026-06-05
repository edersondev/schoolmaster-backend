<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ReportDefinition;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomReportRequestSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_report_request_preserves_definition_snapshot(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.request']);
        $definition = ReportDefinition::factory()->active()->create(['school_id' => $school->id]);
        $period = $this->period($school);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_definition_id' => $definition->uuid,
                'filters' => ['academic_period_id' => $period->uuid],
                'output_formats' => ['pdf'],
            ])
            ->assertAccepted()
            ->assertJsonPath('data.report_definition_id', $definition->uuid);

        $this->assertDatabaseHas('report_definition_snapshots', [
            'report_definition_id' => $definition->id,
            'definition_version' => $definition->version,
        ]);
    }

    private function period(School $school): AcademicPeriod
    {
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
    }
}
