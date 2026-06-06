<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportDefinitionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_definition_create_update_activate_deactivate_delete_and_restore(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $definition = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/report-definitions', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.lifecycle_state', 'draft')
            ->json('data');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/report-definitions/{$definition['id']}", ['name' => 'Updated Attendance'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Attendance');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/report-definitions/{$definition['id']}/activate")
            ->assertOk()
            ->assertJsonPath('data.lifecycle_state', 'active');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/report-definitions/{$definition['id']}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.lifecycle_state', 'inactive');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->deleteJson("/api/v1/report-definitions/{$definition['id']}")
            ->assertOk()
            ->assertJsonPath('data.lifecycle_state', 'deleted');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/report-definitions/{$definition['id']}/restore")
            ->assertOk()
            ->assertJsonPath('data.lifecycle_state', 'inactive');
    }

    public function test_restore_conflicts_for_non_deleted_definition(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $definition = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/report-definitions', $this->payload())
            ->assertCreated()
            ->json('data');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/report-definitions/{$definition['id']}/restore")
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Attendance Overview',
            'description' => 'Daily attendance overview',
            'domain' => 'attendance',
            'fields' => ['student_name', 'attendance_status'],
            'filters' => [['field' => 'academic_period_id', 'operator' => 'equals']],
            'grouping' => [],
            'sorting' => [['field' => 'student_name', 'direction' => 'asc']],
            'output_formats' => ['pdf', 'csv'],
        ];
    }
}
