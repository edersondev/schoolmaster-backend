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
use Tests\TestCase;

final class LongTextAnswerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_long_text_rejects_blank_whitespace_and_oversized_answers(): void
    {
        foreach (['', '   ', str_repeat('a', 10_001), "unsafe\x01control"] as $answer) {
            [$school, $studentUser, $questionnaire, $learningSet] = $this->context();
            $question = $questionnaire->questions->firstOrFail();

            $this->withHeaders($this->headers($studentUser, $school))->postJson('/api/v1/student/questionnaire-responses', [
                'questionnaire_id' => $questionnaire->uuid,
                'learning_set_id' => $learningSet->uuid,
                'answers' => [['question_id' => $question->uuid, 'question_type' => 'long_text', 'answer_text' => $answer]],
            ])->assertUnprocessable();
        }
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

        return [$school, $studentUser, $questionnaire->load('questions'), $learningSet->refresh()];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
