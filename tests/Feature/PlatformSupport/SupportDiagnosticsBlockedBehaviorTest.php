<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportDiagnosticsBlockedBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_reject_undocumented_download_or_emergency_include_inputs(): void
    {
        $school = School::factory()->create();
        $supportUser = $this->createPlatformUser(['platform_support.drill_down']);

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id=".fake()->uuid().'&reason_code=support_case&correlation_id=case-blocked&include=downloads,emergency_access')
            ->assertUnprocessable();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'validation_rejected')
            ->where('outcome', 'rejected')
            ->where('correlation_id', 'case-blocked')
            ->count());
    }
}
