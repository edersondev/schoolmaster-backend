<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_writes_tenant_safe_lifecycle_audit_event(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::failedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/retry", ['reason_code' => 'retry_failed_generation'])
            ->assertAccepted();

        $this->assertDatabaseHas('report_lifecycle_events', [
            'school_id' => $school->id,
            'actor_user_id' => $admin->id,
            'report_run_id' => $run->id,
            'action' => 'retry_requested',
            'outcome' => 'succeeded',
            'target_type' => 'report_run',
            'reason_code' => 'retry_failed_generation',
        ]);
    }
}
