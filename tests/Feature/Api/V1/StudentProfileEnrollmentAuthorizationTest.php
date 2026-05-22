<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileEnrollmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_teacher_and_student_users_do_not_receive_student_admin_access(): void
    {
        $school = School::factory()->create();
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));
        $platform = $this->createPlatformUser();
        $teacher = $this->createTeacher($school);
        $student = User::factory()->create(['school_id' => $school->id]);

        foreach ([$platform, $teacher, $student] as $actor) {
            $this->withToken($this->bearerTokenFor($actor))
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson('/api/v1/student-profiles/'.$profile->uuid)
                ->assertForbidden();
        }
    }
}
