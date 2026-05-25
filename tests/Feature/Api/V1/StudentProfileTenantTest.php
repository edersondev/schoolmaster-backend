<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_operations_allow_resolved_school_session_and_reject_mismatched_school_context(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage']);
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)
            ->getJson('/api/v1/student-profiles')
            ->assertOk();

        $this->withToken($token)
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-004',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
            ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
