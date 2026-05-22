<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EnrollmentHistory;
use App\Models\Guardian;
use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_student_profile_with_guardian_and_initial_history(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage']);
        $guardian = Guardian::query()->create([
            'school_id' => $school->id,
            'full_name' => 'Guardian User',
            'relationship_type' => 'parent',
            'status' => 'active',
        ]);

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-001',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
                'guardian_associations' => [
                    ['guardian_id' => $guardian->uuid, 'relationship_type' => 'mother'],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.school_id', $school->uuid)
            ->assertJsonPath('data.registration_number', 'STU-001')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.guardian_associations.0.relationship_type', 'mother');

        $profileId = $response->json('data.id');

        $this->assertDatabaseHas('student_profiles', [
            'uuid' => $profileId,
            'school_id' => $school->id,
            'registration_number' => 'STU-001',
        ]);
        $this->assertDatabaseHas('guardian_student_profile', [
            'guardian_id' => $guardian->id,
            'relationship_type' => 'mother',
            'status' => 'active',
        ]);
        $this->assertSame(1, EnrollmentHistory::query()->where('event_type', 'created')->count());
    }

    public function test_school_admin_cannot_reuse_registration_number_from_soft_deleted_profile(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);
        $existingProfile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), [
            'registration_number' => 'STU-ARCHIVED',
        ]);
        $existingProfile->delete();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles', [
                'registration_number' => 'STU-ARCHIVED',
                'first_name' => 'Aline',
                'last_name' => 'Silva',
                'enrolled_at' => '2026-02-01',
            ])
            ->assertConflict();
    }
}
