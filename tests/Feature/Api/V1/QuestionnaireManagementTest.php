<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QuestionnaireManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_list_questionnaires_in_resolved_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);
        TeacherWorkflowFactory::questionnaire($otherSchool, $this->createTeacher($otherSchool), ['title' => 'Other Quiz']);

        $created = $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/questionnaires', [
                'title' => 'Quiz',
                'questions' => [
                    ['question_type' => 'true_false', 'prompt' => 'Ready?', 'sequence' => 1],
                    ['question_type' => 'short_text', 'prompt' => 'Why?', 'sequence' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Quiz')
            ->assertJsonCount(2, 'data.questions')
            ->json('data');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/questionnaires')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']])
            ->assertJsonMissing(['title' => 'Other Quiz']);
    }

    public function test_questionnaire_rejects_unsupported_shape(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/questionnaires', [
                'title' => 'Bad Quiz',
                'questions' => [
                    ['question_type' => 'multiple_choice', 'prompt' => 'Pick one', 'options' => ['A'], 'sequence' => 1],
                ],
            ])
            ->assertUnprocessable();
    }
}
