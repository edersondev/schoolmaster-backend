<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileStatusContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_update_returns_documented_lifecycle_result_shape(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/student-profiles/'.$profile->uuid.'/status', [
                'status' => 'inactive',
                'effective_at' => '2026-03-01',
                'reason' => 'Inactive.',
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['student_profile', 'enrollment_history'], 'meta']);
    }
}
