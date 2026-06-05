<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportPlatformAccessBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_does_not_receive_implicit_report_lifecycle_access(): void
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['reports.view']);
        $platformUser = $this->createPlatformUser(['schools.view']);
        $run = StudentReportingFactory::failedReportRun($school, $schoolAdmin);

        $this->withToken($this->bearerTokenFor($platformUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports/'.$run->uuid.'/retry', ['reason_code' => 'retry_failed_generation'])
            ->assertForbidden();
    }
}
