<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EnrollmentHistory;
use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_inactivate_student_profile_and_write_history(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.view', 'student_profiles.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/student-profiles/'.$profile->uuid.'/status', [
                'status' => 'inactive',
                'effective_at' => '2026-03-01',
                'reason' => 'Student left the school.',
            ])
            ->assertOk()
            ->assertJsonPath('data.student_profile.status', 'inactive')
            ->assertJsonPath('data.enrollment_history.event_type', 'inactivated');

        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'status' => 'inactive']);
        $this->assertSame(1, EnrollmentHistory::query()->where('event_type', 'inactivated')->count());
    }
}
