<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportRunSoftDeleteRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_run_delete_and_restore_preserve_generation_state(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle', 'reports.view']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->deleteJson("/api/v1/reports/{$run->uuid}")
            ->assertOk()
            ->assertJsonPath('data.generation_status', 'generated');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports?include_deleted=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $run->uuid);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.generation_status', 'generated')
            ->assertJsonPath('data.deleted_at', null);
    }
}
