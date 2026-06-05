<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Jobs\GenerateReportRunOutputs;
use App\Models\ReportRun;
use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ReportRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_report_run_can_be_retried_without_mutating_source(): void
    {
        Queue::fake();
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $source = StudentReportingFactory::failedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$source->uuid}/retry", ['reason_code' => 'retry_failed_generation'])
            ->assertAccepted()
            ->assertJsonPath('data.generation_status', 'requested')
            ->assertJsonPath('data.source_report_run_id', $source->uuid);

        $this->assertDatabaseHas('report_runs', [
            'id' => $source->id,
            'generation_status' => 'failed',
        ]);
        $this->assertDatabaseHas('report_outputs', [
            'report_run_id' => ReportRun::query()->where('source_report_run_id', $source->id)->value('id'),
            'format' => 'pdf',
            'availability' => 'pending',
        ]);
        Queue::assertPushed(GenerateReportRunOutputs::class);

        $this->assertSame(2, ReportRun::query()->count());
    }

    public function test_non_retryable_report_run_returns_conflict(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/retry", ['reason_code' => 'retry_failed_generation'])
            ->assertConflict();
    }

    public function test_retry_rejects_undocumented_reason_codes(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::failedReportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/reports/{$run->uuid}/retry", ['reason_code' => 'anything'])
            ->assertUnprocessable();
    }
}
