<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StudentAssessmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_submits_assigned_same_school_long_text_and_file_response(): void
    {
        Storage::fake('local');
        [$school, $studentUser, $questionnaire, $learningSet] = $this->advancedContext();
        $questions = $questionnaire->questions->keyBy('question_type');

        $response = $this->withHeaders($this->headers($studentUser, $school))->post('/api/v1/student/questionnaire-responses', [
            'questionnaire_id' => $questionnaire->uuid,
            'learning_set_id' => $learningSet->uuid,
            'answers' => [
                [
                    'question_id' => $questions['long_text']->uuid,
                    'question_type' => 'long_text',
                    'answer_text' => 'Plain text answer',
                ],
                [
                    'question_id' => $questions['file_response']->uuid,
                    'question_type' => 'file_response',
                    'file' => UploadedFile::fake()->createWithContent('answer.txt', 'plain text evidence'),
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.questionnaire_id', $questionnaire->uuid)
            ->assertJsonPath('data.submission_state', 'scan_blocked')
            ->assertJsonPath('data.answers.0.answer_text', 'Plain text answer')
            ->assertJsonPath('data.answers.1.file.scan_status', 'pending')
            ->assertJsonPath('data.file_summary.pending_count', 1);

        $this->assertDatabaseHas('assessment_response_attempts', [
            'school_id' => $school->id,
            'questionnaire_id' => $questionnaire->id,
            'learning_set_id' => $learningSet->id,
        ]);
        $this->assertDatabaseHas('assessment_file_attachments', [
            'school_id' => $school->id,
            'scan_status' => 'pending',
            'availability_state' => 'scan_pending',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'assessment.submission',
            'school_id' => $school->id,
            'outcome' => 'succeeded',
        ]);
    }

    private function advancedContext(array $learningSetAttributes = []): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active', 'current_academic_year_id' => $year->id]);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $learningSet->forceFill(['due_at' => now()->addDay(), ...$learningSetAttributes])->save();
        $questionnaire = Questionnaire::query()->create(['school_id' => $school->id, 'owner_user_id' => $teacher->id, 'title' => 'Advanced quiz', 'status' => 'active']);
        $questionnaire->questions()->create(['question_type' => 'long_text', 'prompt' => 'Essay', 'answer_schema' => ['min_length' => 1, 'max_length' => 10000], 'grading_rule' => ['mode' => 'manual_0_100'], 'visibility' => ['report_visibility' => 'summary_only'], 'sequence' => 1]);
        $questionnaire->questions()->create(['question_type' => 'file_response', 'prompt' => 'Upload', 'answer_schema' => ['allowed_file_categories' => ['pdf', 'image', 'text', 'office'], 'max_file_size_bytes' => 26214400, 'max_files' => 1], 'grading_rule' => ['mode' => 'manual_0_100'], 'visibility' => ['report_visibility' => 'summary_only'], 'sequence' => 2]);
        LearningSetEntry::query()->create(['school_id' => $school->id, 'learning_set_id' => $learningSet->id, 'entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->id, 'sequence' => 1]);

        return [$school, $studentUser, $questionnaire->load('questions'), $learningSet->refresh(), $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($user),
            'X-School-Id' => $school->uuid,
        ];
    }
}
