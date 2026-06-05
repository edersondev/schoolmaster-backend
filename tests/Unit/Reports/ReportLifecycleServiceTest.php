<?php

declare(strict_types=1);

namespace Tests\Unit\Reports;

use App\Services\Reports\ReportLifecycleService;
use Database\Factories\StudentReportingFactory;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_completion_is_ignored_for_canceled_report_run(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::canceledReportRun($school, $admin);

        $completed = app(ReportLifecycleService::class)->completeGeneration($run);

        $this->assertFalse($completed);
        $this->assertDatabaseHas('report_runs', [
            'id' => $run->id,
            'generation_status' => 'canceled',
        ]);
    }

    public function test_completion_sets_run_level_output_expiry(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.lifecycle']);
        $run = StudentReportingFactory::generatingReportRun($school, $admin);
        $output = StudentReportingFactory::availableReportOutput($school, $run, 'pdf');

        $completed = app(ReportLifecycleService::class)->completeGeneration($run);

        $this->assertTrue($completed);
        $this->assertDatabaseHas('report_runs', [
            'id' => $run->id,
            'generation_status' => 'generated',
            'output_expires_at' => $output->expires_at,
        ]);
    }
}
