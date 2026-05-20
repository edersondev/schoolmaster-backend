<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentTeacherContentDownloadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_or_unclean_content_is_not_downloadable(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/teacher-content/'.$content->uuid.'/download')
            ->assertNotFound();
    }
}
