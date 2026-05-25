<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Guardian;
use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_enrollment_happy_path_from_create_to_transfer(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage', 'student_transfers.manage']);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian User', 'relationship_type' => 'parent', 'status' => 'active']);
        $token = $this->bearerTokenFor($admin);

        $created = $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->postJson('/api/v1/student-profiles', [
            'registration_number' => 'STU-HAPPY',
            'first_name' => 'Aline',
            'last_name' => 'Silva',
            'enrolled_at' => '2026-02-01',
            'guardian_associations' => [['guardian_id' => $guardian->uuid, 'relationship_type' => 'mother']],
        ])->assertCreated()->json('data');

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/student-profiles/'.$created['id'])->assertOk();
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/student-profiles')->assertOk();
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->patchJson('/api/v1/student-profiles/'.$created['id'].'/status', [
            'status' => 'inactive',
            'effective_at' => '2026-03-01',
            'reason' => 'Inactive.',
        ])->assertOk();

        $transferProfile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['registration_number' => 'STU-HAPPY-TRANSFER']);
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->postJson('/api/v1/student-profiles/'.$transferProfile->uuid.'/transfer', [
            'effective_at' => '2026-04-01',
            'reason' => 'Transferred.',
        ])->assertOk();
    }
}
