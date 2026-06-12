<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentGradingOutcome;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentAssessmentResponseViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_views_own_response_summary_and_feedback_boundary(): void
    {
        [$school, $studentUser, $attempt, $answer, $teacher] = $this->context();
        AssessmentGradingOutcome::query()->create(['school_id' => $school->id, 'assessment_response_attempt_id' => $attempt->id, 'assessment_answer_id' => $answer->id, 'grader_user_id' => $teacher->id, 'grading_status' => 'graded', 'score' => 91, 'feedback_summary' => 'Visible feedback', 'private_grading_note' => 'Hidden note', 'graded_at' => now()]);
        $attempt->forceFill(['grading_status' => 'graded', 'submission_state' => 'graded', 'earned_points' => 91, 'possible_points' => 100])->save();

        $this->withHeaders($this->headers($studentUser, $school))
            ->getJson("/api/v1/student/questionnaire-responses/{$attempt->uuid}")
            ->assertOk()
            ->assertJsonPath('data.grading_status', 'graded')
            ->assertJsonPath('data.score_summary.percentage', 91)
            ->assertJsonMissingPath('data.grading_outcomes.0.private_grading_note');
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $learningSet = LearningSet::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'academic_period_id' => $period->id, 'title' => 'Set']);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'title' => 'Quiz']);
        $question = QuestionnaireQuestion::query()->create(['questionnaire_id' => $questionnaire->id, 'question_type' => 'long_text', 'prompt' => 'Essay', 'sequence' => 1]);
        $attempt = AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);
        $answer = AssessmentAnswer::query()->create(['school_id' => $school->id, 'assessment_response_attempt_id' => $attempt->id, 'questionnaire_question_id' => $question->id, 'question_type' => 'long_text', 'answer_text' => 'Answer']);

        return [$school, $studentUser, $attempt, $answer, $teacher];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
