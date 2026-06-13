<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedAssessmentReportCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_catalog_exposes_only_aggregate_advanced_assessment_fields(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $response = $this->withHeaders($this->headers($admin, $school))->getJson('/api/v1/report-catalog');

        $response->assertOk()
            ->assertJsonPath('data.domains.4.id', 'advanced_assessments')
            ->assertJsonPath('data.domains.4.fields.0.visibility', 'aggregate_only')
            ->assertJsonMissing(['answer_text', 'storage_path', 'private_grading_note']);
    }

    public function test_report_definition_rejects_private_advanced_assessment_fields(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/report-definitions', [
            'name' => 'Private field report',
            'domain' => 'advanced_assessments',
            'fields' => ['answer_text'],
            'filters' => [],
            'grouping' => [],
            'sorting' => [],
            'output_formats' => ['pdf'],
        ])->assertUnprocessable();
    }

    public function test_advanced_assessments_built_in_report_can_be_requested(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.request']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);

        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/reports', [
            'report_type' => 'advanced_assessments',
            'filters' => ['academic_period_id' => $period->uuid],
            'output_formats' => ['pdf', 'csv'],
        ])->assertAccepted()
            ->assertJsonPath('data.report_type', 'advanced_assessments');
    }

    private function headers($user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
