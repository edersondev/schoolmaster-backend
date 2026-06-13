<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssessmentGradingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_teacher_cannot_grade_response(): void
    {
        [$school, , $attempt, $answer] = $this->context();
        $otherTeacher = $this->createTeacher($school);

        $this->withHeaders($this->headers($otherTeacher, $school))->postJson("/api/v1/questionnaire-responses/{$attempt->uuid}/grading", [
            'grading_outcomes' => [['answer_id' => $answer->uuid, 'status' => 'graded', 'score' => 80]],
        ])->assertForbidden();
    }

    public function test_cross_tenant_grading_uses_permitted_scope_only(): void
    {
        [$school, $teacher, $attempt, $answer] = $this->context();
        $otherSchool = School::factory()->create();

        $this->withHeaders($this->headers($teacher, $otherSchool))->postJson("/api/v1/questionnaire-responses/{$attempt->uuid}/grading", [
            'grading_outcomes' => [['answer_id' => $answer->uuid, 'status' => 'graded', 'score' => 80]],
        ])->assertForbidden();
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'title' => 'Grade quiz', 'status' => 'active']);
        $question = $questionnaire->questions()->create(['question_type' => 'long_text', 'prompt' => 'Essay', 'sequence' => 1]);
        LearningSetEntry::query()->create(['school_id' => $school->id, 'learning_set_id' => $learningSet->id, 'entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->id, 'sequence' => 1]);
        $attempt = AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);
        $answer = AssessmentAnswer::query()->create(['school_id' => $school->id, 'assessment_response_attempt_id' => $attempt->id, 'questionnaire_question_id' => $question->id, 'question_type' => 'long_text', 'answer_text' => 'Essay answer']);

        return [$school, $teacher, $attempt, $answer];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
