<?php

declare(strict_types=1);

namespace Tests\Unit\Assessment;

use App\DTOs\Assessment\AssessmentAnswerInput;
use App\DTOs\Assessment\AssessmentResponseSubmissionData;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Assessment\AssessmentSubmissionEligibilityService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssessmentSubmissionEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_assigned_active_student_assessment_context(): void
    {
        [$school, $studentUser, $questionnaire, $learningSet] = $this->context();
        $question = $questionnaire->questions->firstOrFail();

        $result = app(AssessmentSubmissionEligibilityService::class)->resolve(
            $studentUser,
            new TenantContext($school, 'header', 'resolved'),
            new AssessmentResponseSubmissionData($questionnaire->uuid, $learningSet->uuid, [
                new AssessmentAnswerInput($question->uuid, 'long_text', 'Answer', null),
            ]),
        );

        $this->assertSame($questionnaire->id, $result['questionnaire']->id);
        $this->assertSame($learningSet->id, $result['learning_set']->id);
    }

    public function test_rejects_expired_learning_set(): void
    {
        [$school, $studentUser, $questionnaire, $learningSet] = $this->context(['due_at' => now()->subMinute()]);
        $question = $questionnaire->questions->firstOrFail();

        $this->expectException(ConflictException::class);

        app(AssessmentSubmissionEligibilityService::class)->resolve(
            $studentUser,
            new TenantContext($school, 'header', 'resolved'),
            new AssessmentResponseSubmissionData($questionnaire->uuid, $learningSet->uuid, [
                new AssessmentAnswerInput($question->uuid, 'long_text', 'Answer', null),
            ]),
        );
    }

    private function context(array $learningSetAttributes = []): array
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
        LearningSetEntry::query()->create(['school_id' => $school->id, 'learning_set_id' => $learningSet->id, 'entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->id, 'sequence' => 1]);

        return [$school, $studentUser, $questionnaire->load('questions'), $learningSet->refresh(), $student];
    }
}
