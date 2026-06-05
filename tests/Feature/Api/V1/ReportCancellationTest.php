<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requested_report_run_can_be_canceled_with_predefined_reason(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::requestedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/cancel", ['reason_code' => 'no_longer_needed'])
            ->assertOk()
            ->assertJsonPath('data.generation_status', 'canceled')
            ->assertJsonPath('data.cancellation_reason_code', 'no_longer_needed');
    }

    public function test_cancel_rejects_free_text_reason(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::requestedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/cancel", ['reason_code' => 'because I said so'])
            ->assertUnprocessable();
    }

    public function test_generated_report_run_cannot_be_canceled(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/cancel", ['reason_code' => 'no_longer_needed'])
            ->assertConflict();
    }
}
