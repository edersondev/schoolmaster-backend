<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

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

    private function headers($user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
