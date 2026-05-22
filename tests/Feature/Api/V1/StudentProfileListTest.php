<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileListTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_lists_only_same_school_profiles_with_filters_and_sorting(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view']);

        StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), [
            'registration_number' => 'STU-002',
            'first_name' => 'Bruno',
            'last_name' => 'Costa',
            'status' => 'inactive',
        ]);
        StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), [
            'registration_number' => 'STU-001',
            'first_name' => 'Aline',
            'last_name' => 'Silva',
            'status' => 'active',
        ]);
        StudentEnrollmentFactory::profile($otherSchool, User::factory()->create(['school_id' => $otherSchool->id]), [
            'registration_number' => 'OTHER-001',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student-profiles?status=active&sort=registration_number')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.registration_number', 'STU-001');
    }
}
