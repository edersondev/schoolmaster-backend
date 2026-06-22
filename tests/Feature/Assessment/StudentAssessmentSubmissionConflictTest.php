<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentAssessmentSubmissionConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_submission_conflicts(): void
    {
        [$school, $studentUser, $questionnaire, $learningSet, $student, $period] = $this->context();
        $question = $questionnaire->questions->firstOrFail();
        AssessmentResponseAttempt::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'questionnaire_id' => $questionnaire->id,
            'learning_set_id' => $learningSet->id,
            'academic_period_id' => $period->id,
            'submitted_at' => now(),
        ]);

        $this->withHeaders($this->headers($studentUser, $school))->postJson('/api/v1/student/questionnaire-responses', [
            'questionnaire_id' => $questionnaire->uuid,
            'learning_set_id' => $learningSet->uuid,
            'answers' => [['question_id' => $question->uuid, 'question_type' => 'long_text', 'answer_text' => 'Answer']],
        ])->assertConflict();
    }

    public function test_malformed_answer_rolls_back_without_partial_attempt(): void
    {
        [$school, $studentUser, $questionnaire, $learningSet] = $this->context();
        $question = $questionnaire->questions->firstOrFail();

        $this->withHeaders($this->headers($studentUser, $school))->postJson('/api/v1/student/questionnaire-responses', [
            'questionnaire_id' => $questionnaire->uuid,
            'learning_set_id' => $learningSet->uuid,
            'answers' => [['question_id' => $question->uuid, 'question_type' => 'file_response', 'answer_text' => 'Wrong shape']],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('assessment_response_attempts', 0);
        $this->assertDatabaseCount('assessment_answers', 0);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active', 'current_academic_year_id' => $year->id]);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $learningSet->forceFill(['due_at' => now()->addDay()])->save();
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'title' => 'Advanced quiz', 'status' => 'active']);
        $questionnaire->questions()->create(['question_type' => 'long_text', 'prompt' => 'Essay', 'answer_schema' => ['min_length' => 1, 'max_length' => 10000], 'grading_rule' => ['mode' => 'manual_0_100'], 'visibility' => ['report_visibility' => 'summary_only'], 'sequence' => 1]);
        LearningSetEntry::query()->create(['school_id' => $school->id, 'learning_set_id' => $learningSet->id, 'entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->id, 'sequence' => 1]);

        return [$school, $studentUser, $questionnaire->load('questions'), $learningSet->refresh(), $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
