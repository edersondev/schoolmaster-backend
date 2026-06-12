<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetSupportSchoolDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_user_can_get_redacted_diagnostics_after_both_approval_gates(): void
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $supportUser = $this->createPlatformUser(['platform_support.drill_down']);
        $approver = $this->createPlatformUser(['platform_support.approve']);

        $optInId = $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins", [
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-123456',
            ])
            ->assertCreated()
            ->json('data.id');

        $decisionId = $this->withToken($this->bearerTokenFor($supportUser))
            ->postJson('/api/v1/platform/support-access', [
                'school_id' => $school->uuid,
                'support_opt_in_id' => $optInId,
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-123456',
            ])
            ->assertCreated()
            ->assertJsonPath('data.state', 'requested')
            ->json('data.id');

        $this->withToken($this->bearerTokenFor($approver))
            ->postJson("/api/v1/platform/support-access/{$decisionId}/approve", [
                'reason_code' => 'support_case',
                'correlation_id' => 'case-123456',
            ])
            ->assertOk()
            ->assertJsonPath('data.state', 'approved');

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-123456")
            ->assertOk()
            ->assertJsonPath('data.school_id', $school->uuid)
            ->assertJsonPath('data.support_metadata.diagnostics_scope', 'read_only_redacted')
            ->assertJsonMissingPath('data.report_runs')
            ->assertJsonMissingPath('data.raw_outputs')
            ->assertJsonMissingPath('data.private_file_metadata');

        $this->assertSame(1, PlatformSupportAuditEvent::query()->where('action', 'support_drill_down_access')->where('outcome', 'allowed')->count());
    }
}
