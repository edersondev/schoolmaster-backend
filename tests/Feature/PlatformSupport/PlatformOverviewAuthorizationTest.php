<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformOverviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_actor_without_overview_permission_is_denied_without_cross_school_data(): void
    {
        $school = School::factory()->create(['name' => 'Hidden School']);
        $actor = $this->createPlatformUser(['schools.view']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/schools')
            ->assertForbidden()
            ->assertJsonMissing(['name' => $school->name]);

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'denied_access')
            ->where('reason_code', 'platform_overview_forbidden')
            ->count());
    }

    public function test_platform_actor_without_reporting_permission_is_denied_without_report_summary(): void
    {
        $actor = $this->createPlatformUser(['schools.view']);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/reporting/overview')
            ->assertForbidden()
            ->assertJsonMissingPath('data.reporting_health');

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'denied_access')
            ->where('reason_code', 'platform_reporting_forbidden')
            ->count());
    }
}
