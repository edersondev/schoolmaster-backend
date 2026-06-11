<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\InternalPlatformApproval;
use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportAccessGateDenialTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostics_are_denied_before_internal_platform_approval(): void
    {
        [$school, $supportUser, $decisionId] = $this->createRequestedDecision();

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertConflict();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'conflict_detected')
            ->where('reason_code', 'support_access_inactive')
            ->count());
    }

    public function test_expired_support_decision_is_denied_before_diagnostics(): void
    {
        [$school, $supportUser, $decisionId] = $this->createRequestedDecision(approve: true);

        SupportAccessDecision::query()->where('uuid', $decisionId)->update(['expires_at' => now()->subMinute()]);

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertConflict();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'conflict_detected')
            ->where('reason_code', 'support_access_inactive')
            ->count());
    }

    public function test_expired_target_school_opt_in_is_denied_before_diagnostics(): void
    {
        [$school, $supportUser, $decisionId] = $this->createRequestedDecision(approve: true);
        $decision = SupportAccessDecision::query()->where('uuid', $decisionId)->firstOrFail();

        TargetSchoolSupportOptIn::query()
            ->whereKey($decision->target_school_support_opt_in_id)
            ->update(['expires_at' => now()->subMinute()]);

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertConflict();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'conflict_detected')
            ->where('reason_code', 'support_opt_in_inactive')
            ->count());
    }

    public function test_revoked_target_school_opt_in_is_denied_before_diagnostics(): void
    {
        [$school, $supportUser, $decisionId] = $this->createRequestedDecision(approve: true);
        $decision = SupportAccessDecision::query()->where('uuid', $decisionId)->firstOrFail();

        TargetSchoolSupportOptIn::query()
            ->whereKey($decision->target_school_support_opt_in_id)
            ->update(['state' => 'revoked', 'revoked_at' => now()]);

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertConflict();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'conflict_detected')
            ->where('reason_code', 'support_opt_in_inactive')
            ->count());
    }

    public function test_revoked_internal_platform_approval_is_denied_before_diagnostics(): void
    {
        [$school, $supportUser, $decisionId] = $this->createRequestedDecision(approve: true);
        $decision = SupportAccessDecision::query()->where('uuid', $decisionId)->firstOrFail();

        InternalPlatformApproval::query()
            ->whereKey($decision->internal_platform_approval_id)
            ->update(['state' => 'revoked', 'revoked_at' => now()]);

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertConflict();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'conflict_detected')
            ->where('reason_code', 'platform_approval_inactive')
            ->count());
    }

    public function test_support_access_decision_cannot_be_reused_for_another_school(): void
    {
        [, $supportUser, $decisionId] = $this->createRequestedDecision(approve: true);
        $otherSchool = School::factory()->create();

        $this->withToken($this->bearerTokenFor($supportUser))
            ->getJson("/api/v1/platform/support/schools/{$otherSchool->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertNotFound();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'denied_access')
            ->where('reason_code', 'support_access_mismatched_context')
            ->whereNull('school_id')
            ->count());
    }

    public function test_support_access_decision_cannot_be_reused_by_another_actor(): void
    {
        [$school, , $decisionId] = $this->createRequestedDecision(approve: true);
        $otherSupportUser = $this->createPlatformUser(['platform_support.drill_down']);

        $this->withToken($this->bearerTokenFor($otherSupportUser))
            ->getJson("/api/v1/platform/support/schools/{$school->uuid}/diagnostics?support_access_id={$decisionId}&reason_code=support_case&correlation_id=case-denied")
            ->assertNotFound();

        $this->assertSame(1, PlatformSupportAuditEvent::query()
            ->where('action', 'denied_access')
            ->where('reason_code', 'support_access_mismatched_context')
            ->whereNull('school_id')
            ->count());
    }

    /**
     * @return array{0: School, 1: User, 2: string}
     */
    private function createRequestedDecision(bool $approve = false): array
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $supportUser = $this->createPlatformUser(['platform_support.drill_down']);
        $approver = $this->createPlatformUser(['platform_support.approve']);

        $optInId = $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins", [
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-denied',
            ])
            ->json('data.id');

        $decisionId = $this->withToken($this->bearerTokenFor($supportUser))
            ->postJson('/api/v1/platform/support-access', [
                'school_id' => $school->uuid,
                'support_opt_in_id' => $optInId,
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-denied',
            ])
            ->json('data.id');

        if ($approve) {
            $this->withToken($this->bearerTokenFor($approver))
                ->postJson("/api/v1/platform/support-access/{$decisionId}/approve", [
                    'reason_code' => 'support_case',
                    'correlation_id' => 'case-denied',
                ]);
        }

        return [$school, $supportUser, $decisionId];
    }
}
