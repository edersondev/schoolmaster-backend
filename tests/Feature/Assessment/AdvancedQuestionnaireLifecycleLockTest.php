<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AssessmentResponseAttempt;
use App\Models\LearningSetAssignment;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedQuestionnaireLifecycleLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_advanced_question_meaning_changes_after_learning_set_assignment(): void
    {
        [$school, $teacher, $questionnaire] = $this->assignedQuestionnaireContext();

        $this->withHeaders($this->headers($teacher, $school))->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", [
            'questions' => [[
                'question_type' => 'long_text',
                'prompt' => 'Changed prompt',
                'answer_schema' => ['min_length' => 1, 'max_length' => 10000],
                'grading_rule' => ['mode' => 'manual_0_100'],
                'visibility' => ['report_visibility' => 'summary_only'],
                'sequence' => 1,
            ]],
        ])->assertConflict();

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'assessment.conflict',
            'school_id' => $school->id,
            'outcome' => 'conflicted',
        ]);
    }

    public function test_rejects_advanced_question_meaning_changes_after_response_submission(): void
    {
        [$school, $teacher, $questionnaire, $student, $period] = $this->assignedQuestionnaireContext();
        $learningSet = $questionnaire->learningSetEntries()->firstOrFail()->learningSet;

        AssessmentResponseAttempt::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'questionnaire_id' => $questionnaire->id,
            'learning_set_id' => $learningSet->id,
            'academic_period_id' => $period->id,
            'submission_state' => 'submitted',
            'grading_status' => 'needs_review',
            'submitted_at' => now(),
        ]);

        $this->withHeaders($this->headers($teacher, $school))->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", [
            'questions' => [[
                'question_type' => 'file_response',
                'prompt' => 'Changed upload',
                'answer_schema' => ['allowed_file_categories' => ['pdf', 'image', 'text', 'office'], 'max_file_size_bytes' => 26214400, 'max_files' => 1],
                'grading_rule' => ['mode' => 'manual_0_100'],
                'visibility' => ['report_visibility' => 'summary_only'],
                'sequence' => 1,
            ]],
        ])->assertConflict();
    }

    private function assignedQuestionnaireContext(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
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
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $questionnaire = Questionnaire::query()->create([
            'school_id' => $school->id,
            'owner_user_id' => $teacher->id,
            'title' => 'Advanced quiz',
            'status' => 'active',
        ]);
        $questionnaire->questions()->create([
            'question_type' => 'long_text',
            'prompt' => 'Essay',
            'answer_schema' => ['min_length' => 1, 'max_length' => 10000],
            'grading_rule' => ['mode' => 'manual_0_100'],
            'visibility' => ['report_visibility' => 'summary_only'],
            'sequence' => 1,
        ]);
        LearningSetEntry::query()->create([
            'school_id' => $school->id,
            'learning_set_id' => $learningSet->id,
            'entry_type' => 'questionnaire',
            'entry_reference_id' => $questionnaire->id,
            'sequence' => 1,
        ]);
        LearningSetAssignment::query()->firstOrCreate([
            'school_id' => $school->id,
            'learning_set_id' => $learningSet->id,
            'student_profile_id' => $student->id,
        ], [
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        return [$school, $teacher, $questionnaire->load('learningSetEntries.learningSet'), $student, $period];
    }

    /**
     * @return array<string, string>
     */
    private function headers($user, School $school): array
    {
        return [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($user),
            'X-School-Id' => $school->uuid,
        ];
    }
}
