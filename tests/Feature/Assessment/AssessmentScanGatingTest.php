<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentFileAttachment;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssessmentScanGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_scan_blocks_grading(): void
    {
        [$school, $teacher, $attempt, $answer] = $this->context('pending', 'scan_pending');

        $this->withHeaders($this->headers($teacher, $school))->postJson("/api/v1/questionnaire-responses/{$attempt->uuid}/grading", [
            'grading_outcomes' => [['answer_id' => $answer->uuid, 'status' => 'graded', 'score' => 0]],
        ])->assertConflict();
    }

    public function test_failed_scan_allows_zero_or_exempt_only(): void
    {
        [$school, $teacher, $attempt, $answer] = $this->context('failed', 'scan_failed');

        $this->withHeaders($this->headers($teacher, $school))->postJson("/api/v1/questionnaire-responses/{$attempt->uuid}/grading", [
            'grading_outcomes' => [['answer_id' => $answer->uuid, 'status' => 'graded', 'score' => 50]],
        ])->assertUnprocessable();

        $this->withHeaders($this->headers($teacher, $school))->postJson("/api/v1/questionnaire-responses/{$attempt->uuid}/grading", [
            'grading_outcomes' => [['answer_id' => $answer->uuid, 'status' => 'exempted']],
        ])->assertOk();
    }

    private function context(string $scanStatus, string $availability): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'title' => 'File quiz', 'status' => 'active']);
        $question = $questionnaire->questions()->create(['question_type' => 'file_response', 'prompt' => 'Upload', 'sequence' => 1]);
        LearningSetEntry::query()->create(['school_id' => $school->id, 'learning_set_id' => $learningSet->id, 'entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->id, 'sequence' => 1]);
        $attempt = AssessmentResponseAttempt::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'questionnaire_id' => $questionnaire->id, 'learning_set_id' => $learningSet->id, 'academic_period_id' => $period->id, 'submitted_at' => now()]);
        $answer = AssessmentAnswer::query()->create(['school_id' => $school->id, 'assessment_response_attempt_id' => $attempt->id, 'questionnaire_question_id' => $question->id, 'question_type' => 'file_response']);
        AssessmentFileAttachment::query()->create(['school_id' => $school->id, 'assessment_answer_id' => $answer->id, 'original_filename' => 'answer.txt', 'sanitized_filename' => 'answer.txt', 'declared_content_type' => 'text/plain', 'detected_content_type' => 'text/plain', 'file_category' => 'text', 'file_size_bytes' => 9, 'storage_path' => 'answer.txt', 'scan_status' => $scanStatus, 'availability_state' => $availability, 'uploaded_at' => now()]);

        return [$school, $teacher, $attempt, $answer->refresh()];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
