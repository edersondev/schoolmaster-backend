<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileLifecycleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_student_profile_cannot_use_active_student_self_view(): void
    {
        $school = School::factory()->create();
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        StudentEnrollmentFactory::profile($school, $studentUser, ['status' => 'inactive']);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades')
            ->assertForbidden();
    }
}
