<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolSupportOptInTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_with_explicit_permission_can_create_and_revoke_opt_in(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $token = $this->bearerTokenFor($admin);

        $optInId = $this->withToken($token)
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins", [
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-abcdef',
            ])
            ->assertCreated()
            ->assertJsonPath('data.state', 'approved')
            ->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins/{$optInId}/revoke", [
                'reason_code' => 'case_closed',
                'correlation_id' => 'case-abcdef',
            ])
            ->assertOk()
            ->assertJsonPath('data.state', 'revoked');
    }

    public function test_school_admin_without_explicit_permission_cannot_create_opt_in(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view']);

        $this->withToken($this->bearerTokenFor($admin))
            ->postJson("/api/v1/schools/{$school->uuid}/support-opt-ins", [
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose reporting issue',
                'correlation_id' => 'case-abcdef',
            ])
            ->assertForbidden();
    }
}
