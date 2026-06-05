<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\ReportDefinition;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportDefinitionNameUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_non_deleted_definition_names_conflict(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);
        ReportDefinition::factory()->create(['school_id' => $school->id, 'name' => 'Attendance Overview']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/report-definitions', $this->payload())
            ->assertConflict();
    }

    public function test_restore_conflicts_when_name_was_reused(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);
        $deleted = ReportDefinition::factory()->create(['school_id' => $school->id, 'name' => 'Attendance Overview']);
        $deleted->delete();
        ReportDefinition::factory()->create(['school_id' => $school->id, 'name' => 'Attendance Overview']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/report-definitions/{$deleted->uuid}/restore")
            ->assertConflict();
    }

    private function payload(): array
    {
        return [
            'name' => 'Attendance Overview',
            'domain' => 'attendance',
            'fields' => ['student_name'],
            'filters' => [['field' => 'academic_period_id', 'operator' => 'equals']],
            'grouping' => [],
            'sorting' => [],
            'output_formats' => ['pdf'],
        ];
    }
}
