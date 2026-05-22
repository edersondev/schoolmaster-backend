<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_student_profile_operations_use_documented_envelopes(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage', 'student_transfers.manage']);
        $token = $this->bearerTokenFor($admin);

        $created = $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->postJson('/api/v1/student-profiles', [
            'registration_number' => 'STU-RESP',
            'first_name' => 'Aline',
            'last_name' => 'Silva',
            'enrolled_at' => '2026-02-01',
        ])->assertCreated()->assertJsonStructure(['data', 'meta'])->json('data');

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/student-profiles')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/student-profiles/'.$created['id'])->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->patchJson('/api/v1/student-profiles/'.$created['id'].'/status', [
            'status' => 'inactive',
            'effective_at' => '2026-03-01',
            'reason' => 'Inactive.',
        ])->assertOk()->assertJsonStructure(['data', 'meta']);

        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['registration_number' => 'STU-TRANSFER-RESP']);
        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->postJson('/api/v1/student-profiles/'.$profile->uuid.'/transfer', [
            'effective_at' => '2026-04-01',
            'reason' => 'Transferred.',
        ])->assertOk()->assertJsonStructure(['data', 'meta']);
    }
}
