<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetPlatformReportingOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_actor_can_get_minimized_reporting_overview(): void
    {
        $school = School::factory()->create();
        $requester = $this->createSchoolAdmin($school, ['reports.request']);

        for ($i = 0; $i < 3; $i++) {
            ReportRun::query()->create([
                'school_id' => $school->id,
                'requested_by_user_id' => $requester->id,
                'report_type' => 'attendance',
                'filter_summary' => ['grade' => 'hidden'],
                'output_formats' => ['pdf'],
                'status' => 'failed',
                'generation_status' => 'failed',
                'outputs_available' => false,
                'failure_reason_code' => 'source_unavailable',
            ]);
        }

        $actor = $this->createPlatformUser(['platform_support.reporting']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/reporting/overview')
            ->assertOk()
            ->assertJsonPath('data.reporting_health.failed.value', null)
            ->assertJsonPath('data.reporting_health.failed.suppressed', true)
            ->assertJsonMissingPath('data.report_runs')
            ->assertJsonMissingPath('data.filter_summary')
            ->assertJsonMissingPath('data.storage_path');

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'platform_reporting_overview_access')
            ->where('outcome', 'allowed')
            ->count());
    }

    public function test_reporting_overview_applies_school_status_and_report_source_filters(): void
    {
        $activeSchool = School::factory()->create(['status' => 'active']);
        $inactiveSchool = School::factory()->create(['status' => 'inactive']);
        $activeRequester = $this->createSchoolAdmin($activeSchool, ['reports.request']);
        $inactiveRequester = $this->createSchoolAdmin($inactiveSchool, ['reports.request']);
        $customDefinition = ReportDefinition::factory()->create([
            'school_id' => $inactiveSchool->id,
            'created_by_user_id' => $inactiveRequester->id,
            'updated_by_user_id' => $inactiveRequester->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            ReportRun::query()->create([
                'school_id' => $activeSchool->id,
                'requested_by_user_id' => $activeRequester->id,
                'report_type' => 'attendance',
                'filter_summary' => [],
                'output_formats' => ['pdf'],
                'status' => 'failed',
                'generation_status' => 'failed',
                'outputs_available' => false,
                'failure_reason_code' => 'source_unavailable',
            ]);

            ReportRun::query()->create([
                'school_id' => $inactiveSchool->id,
                'requested_by_user_id' => $inactiveRequester->id,
                'report_type' => 'attendance',
                'filter_summary' => [],
                'output_formats' => ['pdf'],
                'status' => 'generated',
                'generation_status' => 'generated',
                'outputs_available' => true,
                'report_definition_id' => $customDefinition->id,
            ]);
        }

        $actor = $this->createPlatformUser(['platform_support.reporting']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/reporting/overview?school_status=active&report_source=built_in')
            ->assertOk()
            ->assertJsonPath('data.reporting_health.failed.value', 5)
            ->assertJsonPath('data.reporting_health.generated.value', 0);
    }

    public function test_reporting_overview_counts_canceled_report_runs(): void
    {
        $school = School::factory()->create();
        $requester = $this->createSchoolAdmin($school, ['reports.request']);

        for ($i = 0; $i < 5; $i++) {
            ReportRun::query()->create([
                'school_id' => $school->id,
                'requested_by_user_id' => $requester->id,
                'report_type' => 'attendance',
                'filter_summary' => [],
                'output_formats' => ['pdf'],
                'status' => 'canceled',
                'generation_status' => 'canceled',
                'outputs_available' => false,
                'cancellation_reason_code' => 'no_longer_needed',
            ]);
        }

        $actor = $this->createPlatformUser(['platform_support.reporting']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/reporting/overview')
            ->assertOk()
            ->assertJsonPath('data.reporting_health.canceled.value', 5)
            ->assertJsonPath('data.lifecycle_states.canceled.value', 5)
            ->assertJsonMissingPath('data.reporting_health.cancelled')
            ->assertJsonMissingPath('data.lifecycle_states.cancelled');
    }
}
