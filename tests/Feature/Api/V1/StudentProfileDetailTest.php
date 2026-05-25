<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_same_school_inactive_profile_without_cross_tenant_disclosure(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), [
            'status' => 'inactive',
            'registration_number' => 'STU-003',
        ]);
        $otherProfile = StudentEnrollmentFactory::profile($otherSchool, User::factory()->create(['school_id' => $otherSchool->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles/'.$profile->uuid)
            ->assertOk()
            ->assertJsonPath('data.id', $profile->uuid)
            ->assertJsonPath('data.status', 'inactive');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles/'.$otherProfile->uuid)
            ->assertNotFound();
    }
}
