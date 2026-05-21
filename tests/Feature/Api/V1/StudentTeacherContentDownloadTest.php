<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StudentTeacherContentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_downloads_assigned_clean_content(): void
    {
        Storage::fake('teacher_content');
        [$school, $studentUser, $student, $teacher, $period] = $this->context();
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        Storage::disk('teacher_content')->put($content->storage_path, 'file');
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'content_item', $content->id);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->get('/api/v1/student/teacher-content/'.$content->uuid.'/download')
            ->assertOk();
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $studentUser, $student, $teacher, $period];
    }
}
