<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_student_profile_operations_deny_cross_tenant_profiles(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage', 'student_transfers.manage']);
        $otherProfile = StudentEnrollmentFactory::profile($otherSchool, User::factory()->create(['school_id' => $otherSchool->id]));
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/student-profiles/'.$otherProfile->uuid)->assertNotFound();
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->patchJson('/api/v1/student-profiles/'.$otherProfile->uuid.'/status', [
            'status' => 'inactive',
            'effective_at' => '2026-03-01',
            'reason' => 'Blocked.',
        ])->assertNotFound();
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->postJson('/api/v1/student-profiles/'.$otherProfile->uuid.'/transfer', [
            'effective_at' => '2026-04-01',
            'reason' => 'Blocked.',
        ])->assertNotFound();
    }
}
