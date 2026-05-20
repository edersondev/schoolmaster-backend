<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Jobs\GenerateReportRunOutputs;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ReportRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_requests_async_report(): void
    {
        Queue::fake();
        [$school, $admin, $period] = $this->context();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports', [
                'report_type' => 'attendance',
                'filters' => ['academic_period_id' => $period->uuid],
            ])
            ->assertAccepted()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.outputs_available', false);

        Queue::assertPushed(GenerateReportRunOutputs::class);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.request', 'reports.view']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);

        return [$school, $admin, $period];
    }
}
