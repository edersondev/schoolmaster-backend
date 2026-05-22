<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_rejects_duplicates_undocumented_fields_and_cross_tenant_guardians(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage']);
        $token = $this->bearerTokenFor($admin);

        StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), [
            'registration_number' => 'STU-005',
        ]);
        $guardian = Guardian::query()->create([
            'school_id' => $otherSchool->id,
            'full_name' => 'Other Guardian',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $payload = [
            'registration_number' => 'STU-005',
            'first_name' => 'Aline',
            'last_name' => 'Silva',
            'enrolled_at' => '2026-02-01',
            'unknown' => 'blocked',
            'guardian_associations' => [
                ['guardian_id' => $guardian->uuid, 'relationship_type' => 'parent'],
            ],
        ];

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseMissing('student_profiles', ['first_name' => 'Aline']);
    }
}
