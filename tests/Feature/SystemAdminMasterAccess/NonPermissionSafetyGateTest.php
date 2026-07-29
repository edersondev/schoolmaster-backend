<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NonPermissionSafetyGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_access_does_not_bypass_file_scan_or_support_opt_in_ownership(): void
    {
        $school = School::factory()->create();
        $owner = $this->createTeacher($school);
        $pendingContent = TeacherWorkflowFactory::cleanContent($school, $owner, ['scan_status' => 'pending']);
        $master = $this->createSystemAdministrator();
        $token = $this->bearerTokenFor($master);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-content/'.$pendingContent->uuid.'/download')
            ->assertForbidden();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/schools/'.$school->uuid.'/support-opt-ins', [
                'reason_code' => 'support_case',
                'purpose' => 'Attempt unsupported opt-in',
                'correlation_id' => 'master-safety-gate',
            ])
            ->assertForbidden();
    }

    public function test_master_access_does_not_bypass_internal_support_approval(): void
    {
        $school = School::factory()->create();
        $schoolAdmin = $this->createSchoolAdmin($school, ['platform_support.opt_in']);
        $master = $this->createSystemAdministrator();
        $optInId = $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->postJson('/api/v1/schools/'.$school->uuid.'/support-opt-ins', [
                'reason_code' => 'support_case',
                'purpose' => 'Approve diagnostics access',
                'correlation_id' => 'master-approval-gate',
            ])
            ->assertCreated()
            ->json('data.id');

        $decisionId = $this->withToken($this->bearerTokenFor($master))
            ->postJson('/api/v1/platform/support-access', [
                'school_id' => $school->uuid,
                'support_opt_in_id' => $optInId,
                'reason_code' => 'support_case',
                'purpose' => 'Diagnose a school issue',
                'correlation_id' => 'master-approval-gate',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($this->bearerTokenFor($master))
            ->getJson('/api/v1/platform/support/schools/'.$school->uuid.'/diagnostics?support_access_id='.$decisionId.'&reason_code=support_case&correlation_id=master-approval-gate')
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');
    }
}
