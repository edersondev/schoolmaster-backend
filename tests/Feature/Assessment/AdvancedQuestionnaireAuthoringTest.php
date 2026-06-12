<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\AuditEvent;
use App\Models\QuestionnaireQuestion;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedQuestionnaireAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_author_mixed_legacy_and_advanced_questions(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $response = $this->withHeaders($this->headers($teacher, $school))->postJson('/api/v1/questionnaires', [
            'title' => 'Advanced quiz',
            'description' => 'Mixed question types',
            'questions' => [
                [
                    'question_type' => 'multiple_choice',
                    'prompt' => 'Pick one',
                    'options' => ['A', 'B'],
                    'correct_answer' => 'A',
                    'sequence' => 1,
                ],
                [
                    'question_type' => 'long_text',
                    'prompt' => 'Explain your reasoning',
                    'answer_schema' => ['min_length' => 1, 'max_length' => 10000],
                    'grading_rule' => ['mode' => 'manual_0_100', 'allow_exempt' => true],
                    'visibility' => ['student_answer_visible' => true, 'report_visibility' => 'summary_only'],
                    'sequence' => 2,
                ],
                [
                    'question_type' => 'file_response',
                    'prompt' => 'Upload your work',
                    'answer_schema' => [
                        'allowed_file_categories' => ['pdf', 'image', 'text', 'office'],
                        'max_file_size_bytes' => 26214400,
                        'max_files' => 1,
                    ],
                    'grading_rule' => ['mode' => 'manual_0_100'],
                    'visibility' => ['report_visibility' => 'summary_only'],
                    'sequence' => 3,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Advanced quiz')
            ->assertJsonPath('data.questions.1.question_type', 'long_text')
            ->assertJsonPath('data.questions.2.answer_schema.max_files', 1);

        $this->assertDatabaseHas('questionnaire_questions', [
            'question_type' => 'long_text',
            'prompt' => 'Explain your reasoning',
        ]);
        $this->assertDatabaseHas('questionnaire_questions', [
            'question_type' => 'file_response',
            'prompt' => 'Upload your work',
        ]);
        $this->assertSame(3, QuestionnaireQuestion::query()->count());
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'assessment.authoring',
            'school_id' => $school->id,
            'outcome' => 'succeeded',
        ]);
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
