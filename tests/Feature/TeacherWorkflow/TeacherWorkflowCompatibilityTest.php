<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_create_list_student_self_view_and_legacy_direct_assignment_reads_still_work(): void
    {
        [$school, $teacher, $studentUser, $student, $period] = $this->context();
        $teacherHeaders = $this->headers($teacher, $school);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $activeGrade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'grade_value' => 72, 'status' => 'inactive', 'recorded_at' => now()]);

        $this->withHeaders($teacherHeaders)->getJson('/api/v1/teacher-content')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->withHeaders($teacherHeaders)->postJson('/api/v1/questionnaires', ['title' => 'Compatibility Quiz', 'questions' => [['question_type' => 'true_false', 'prompt' => 'Ready?', 'sequence' => 1]]])->assertCreated();
        $this->withHeaders($teacherHeaders)->getJson("/api/v1/learning-sets/{$learningSet->uuid}")->assertOk()->assertJsonPath('data.assignments.0.assignment_mode', 'legacy_direct');

        $this->withHeaders($this->headers($studentUser, $school))
            ->getJson('/api/v1/student/grades?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('data.0.id', $activeGrade->uuid)
            ->assertJsonPath('meta.total', 1);

        $this->assertDatabaseHas('learning_set_assignments', ['learning_set_id' => $learningSet->id, 'student_profile_id' => $student->id, 'assignment_mode' => 'legacy_direct']);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $studentUser, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
