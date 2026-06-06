<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_without_lifecycle_permission_is_denied(): void
    {
        $school = School::factory()->create();
        $viewer = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::failedReportRun($school, $viewer);

        $this->withToken($this->bearerTokenFor($viewer))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/retry", ['reason_code' => 'retry_failed_generation'])
            ->assertForbidden();
    }

    public function test_cross_tenant_report_lifecycle_target_is_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $otherAdmin = $this->createSchoolAdmin($otherSchool, ['reports.lifecycle']);
        $run = StudentReportingFactory::failedReportRun($otherSchool, $otherAdmin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/retry", ['reason_code' => 'retry_failed_generation'])
            ->assertNotFound();
    }
}
