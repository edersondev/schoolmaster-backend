<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ReportDefinition;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsupported_output_format_is_rejected_for_report_type(): void
    {
        $school = School::factory()->create();
        $period = $this->period($school);
        $admin = $this->createSchoolAdmin($school, ['reports.request']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_type' => 'academic_structure',
                'filters' => ['academic_period_id' => $period->uuid],
                'output_formats' => ['xlsx'],
            ])
            ->assertUnprocessable();
    }

    public function test_duplicate_output_formats_are_rejected(): void
    {
        $school = School::factory()->create();
        $period = $this->period($school);
        $admin = $this->createSchoolAdmin($school, ['reports.request']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_type' => 'attendance',
                'filters' => ['academic_period_id' => $period->uuid],
                'output_formats' => ['pdf', 'pdf'],
            ])
            ->assertUnprocessable();
    }

    public function test_custom_report_request_rejects_runtime_filters_from_another_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $definition = ReportDefinition::factory()->active()->create(['school_id' => $school->id]);
        $foreignPeriod = $this->period($otherSchool);
        $admin = $this->createSchoolAdmin($school, ['reports.request']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_definition_id' => $definition->uuid,
                'filters' => ['academic_period_id' => $foreignPeriod->uuid],
                'output_formats' => ['pdf'],
            ])
            ->assertUnprocessable();
    }

    public function test_custom_report_request_rejects_filters_not_declared_on_definition(): void
    {
        $school = School::factory()->create();
        $definition = ReportDefinition::factory()->active()->create([
            'school_id' => $school->id,
            'filters' => [['field' => 'academic_period_id', 'operator' => 'equals']],
        ]);
        $period = $this->period($school);
        $admin = $this->createSchoolAdmin($school, ['reports.request']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_definition_id' => $definition->uuid,
                'filters' => [
                    'academic_period_id' => $period->uuid,
                    'user_id' => fake()->uuid(),
                ],
                'output_formats' => ['pdf'],
            ])
            ->assertUnprocessable();
    }

    private function period(School $school): AcademicPeriod
    {
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        return AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
    }
}
