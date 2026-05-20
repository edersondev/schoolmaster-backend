<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningSetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_list_learning_sets_for_selected_students(): void
    {
        [$school, $teacher, $period, $student] = $this->context();
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $teacher);

        $created = $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/learning-sets', [
                'academic_period_id' => $period->uuid,
                'title' => 'Week 1',
                'entries' => [
                    ['entry_type' => 'content_item', 'entry_reference_id' => $content->uuid, 'sequence' => 1],
                    ['entry_type' => 'questionnaire', 'entry_reference_id' => $questionnaire->uuid, 'sequence' => 2],
                ],
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Week 1')
            ->assertJsonCount(2, 'data.entries')
            ->assertJsonCount(1, 'data.assignments')
            ->json('data');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/learning-sets')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_learning_set_creation_rejects_unclean_content_without_partial_persistence(): void
    {
        [$school, $teacher, $period, $student] = $this->context();
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/learning-sets', [
                'academic_period_id' => $period->uuid,
                'title' => 'Week 1',
                'entries' => [
                    ['entry_type' => 'content_item', 'entry_reference_id' => $content->uuid, 'sequence' => 1],
                ],
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('learning_sets', 0);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
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
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'status' => 'active',
            'current_academic_year_id' => $year->id,
        ]);

        return [$school, $teacher, $period, $student];
    }
}
