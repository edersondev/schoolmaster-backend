<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_us1_operations_return_documented_success_envelopes(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage']);
        $token = $this->bearerTokenFor($admin);

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-007',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'school_id', 'registration_number', 'status', 'enrollment_history'], 'meta'])
            ->json('data');

        StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['registration_number' => 'STU-008']);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total']]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles/'.$created['id'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'school_id', 'registration_number', 'guardian_associations', 'enrollment_history'], 'meta']);
    }
}
