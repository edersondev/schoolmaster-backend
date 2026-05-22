<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentValidationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_enrollment_validation_rejects_undocumented_inputs(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage', 'student_transfers.manage']);
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles?unsupported=true')
            ->assertUnprocessable();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-VAL',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
                'extra_field' => 'blocked',
            ])
            ->assertUnprocessable();
    }
}
