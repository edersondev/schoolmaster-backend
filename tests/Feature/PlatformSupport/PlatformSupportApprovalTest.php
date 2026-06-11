<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformSupportApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_approver_can_approve_and_revoke_support_access(): void
    {
        [$decisionId, $approver] = $this->createRequestedDecision();
        $token = $this->bearerTokenFor($approver);

        $this->withToken($token)
            ->postJson("/api/v1/platform/support-access/{$decisionId}/approve", [
                'reason_code' => 'support_case',
                'correlation_id' => 'case-approval',
            ])
            ->assertOk()
            ->assertJsonPath('data.platform_approval_state', 'approved');

        $this->withToken($token)
            ->postJson("/api/v1/platform/support-access/{$decisionId}/revoke", [
                'reason_code' => 'case_closed',
                'correlation_id' => 'case-approval',
            ])
            ->assertOk()
            ->assertJsonPath('data.state', 'revoked');
    }

    /**
     * @return array{0: string, 1: User}
     */
    private function createRequestedDecision(): array
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $supportUser = $this->createPlatformUser(['platform_support.drill_down']);
        $approver = $this->createPlatformUser(['platform_support.approve']);

        $optInId = $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins", [
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-approval',
            ])
            ->json('data.id');

        $decisionId = $this->withToken($this->bearerTokenFor($supportUser))
            ->postJson('/api/v1/platform/support-access', [
                'school_id' => $school->uuid,
                'support_opt_in_id' => $optInId,
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-approval',
            ])
            ->json('data.id');

        return [$decisionId, $approver];
    }
}
