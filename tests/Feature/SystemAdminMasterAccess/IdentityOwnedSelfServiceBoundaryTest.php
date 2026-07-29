<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdentityOwnedSelfServiceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_role_does_not_infer_student_or_guardian_identity(): void
    {
        $school = School::factory()->create();
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'registration_number' => 'MASTER-BOUNDARY-1',
            'first_name' => 'Other',
            'last_name' => 'Student',
            'status' => 'active',
            'enrolled_at' => '2026-01-01',
            'status_effective_at' => '2026-01-01',
        ]);
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardian/students')
            ->assertForbidden();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/guardian/students/'.$student->uuid)
            ->assertForbidden()
            ->assertJsonMissing(['registration_number' => $student->registration_number]);
    }
}
