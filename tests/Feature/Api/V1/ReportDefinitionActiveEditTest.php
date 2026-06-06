<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\ReportDefinition;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportDefinitionActiveEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_definition_allows_metadata_update_and_rejects_structural_update(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);
        $definition = ReportDefinition::factory()->active()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/report-definitions/{$definition->uuid}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/report-definitions/{$definition->uuid}", ['fields' => ['student_name']])
            ->assertConflict();
    }
}
