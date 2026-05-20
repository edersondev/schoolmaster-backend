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
use Tests\TestCase;

final class StudentLearningTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_own_learning_sets_for_required_academic_period(): void
    {
        [$school, $studentUser, $student, $teacher, $period] = $this->context();
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['title' => 'Clean file']);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'content_item', $content->id, 1);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/learning-sets?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('data.0.id', $learningSet->uuid)
            ->assertJsonPath('data.0.entries.0.content_item.title', 'Clean file')
            ->assertJsonPath('data.0.entries.0.content_item.download_available', true)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_student_learning_timeline_requires_academic_period(): void
    {
        [$school, $studentUser] = $this->context();

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/learning-sets')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'status' => 'active',
            'current_academic_year_id' => $year->id,
        ]);

        return [$school, $studentUser, $student, $teacher, $period];
    }
}
