<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherMaterialsHistoricalMeaningTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_and_questionnaire_meaning_changes_are_rejected_after_learning_set_use(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
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
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $teacher);

        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'content_item', $content->id, 1);
        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'questionnaire', $questionnaire->id, 2);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/teacher-content/{$content->uuid}", ['title' => 'Changed'])
            ->assertConflict();

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", [
                'questions' => [
                    ['question_type' => 'true_false', 'prompt' => 'Changed?', 'sequence' => 1],
                ],
            ])
            ->assertConflict();
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
