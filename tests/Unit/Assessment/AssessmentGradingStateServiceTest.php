<?php

declare(strict_types=1);

namespace Tests\Unit\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentGradingOutcome;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Assessment\AssessmentResponseStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssessmentGradingStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_file_sets_scan_blocked_state(): void
    {
        [$attempt, $answer] = $this->context();
        AssessmentFileAttachment::query()->create(['school_id' => $attempt->school_id, 'assessment_answer_id' => $answer->id, 'original_filename' => 'answer.txt', 'sanitized_filename' => 'answer.txt', 'declared_content_type' => 'text/plain', 'file_category' => 'text', 'file_size_bytes' => 9, 'storage_path' => 'answer.txt', 'scan_status' => 'pending', 'availability_state' => 'scan_pending', 'uploaded_at' => now()]);

        $updated = app(AssessmentResponseStateService::class)->refreshFromAnswers($attempt);

        $this->assertSame('scan_blocked', $updated->submission_state);
    }

    public function test_all_graded_answers_sets_graded_state(): void
    {
        [$attempt, $answer, $grader] = $this->context();
        AssessmentGradingOutcome::query()->create(['school_id' => $attempt->school_id, 'assessment_response_attempt_id' => $attempt->id, 'assessment_answer_id' => $answer->id, 'grader_user_id' => $grader->id, 'grading_status' => 'graded', 'score' => 90, 'graded_at' => now()]);

        $updated = app(AssessmentResponseStateService::class)->refreshFromAnswers($attempt);

        $this->assertSame('graded', $updated->grading_status);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $grader = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $learningSet = LearningSet::query()->create(['school_id' => $school->id, 'owner_user_id' => $grader->id, 'academic_period_id' => $period->id, 'title' => 'Set']);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $grader->id, 'title' => 'Quiz']);
        $question = QuestionnaireQuestion::query()->create(['questionnaire_id' => $questionnaire->id, 'question_type' => 'long_text', 'prompt' => 'Essay', 'sequence' => 1]);
        $attempt = AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);
        $answer = AssessmentAnswer::query()->create(['school_id' => $school->id, 'assessment_response_attempt_id' => $attempt->id, 'questionnaire_question_id' => $question->id, 'question_type' => 'long_text', 'answer_text' => 'Answer']);

        return [$attempt, $answer, $grader];
    }
}
