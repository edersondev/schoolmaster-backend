<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\Questionnaire;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedQuestionnaireValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_unsupported_question_type_and_audits_validation_failure(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $this->withHeaders($this->headers($teacher, $school))->postJson('/api/v1/questionnaires', [
            'title' => 'Bad quiz',
            'questions' => [
                ['question_type' => 'rating', 'prompt' => 'Rate', 'sequence' => 1],
            ],
        ])->assertUnprocessable();

        $this->assertSame(0, Questionnaire::query()->count());
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'assessment.validation',
            'school_id' => $school->id,
            'outcome' => 'rejected',
        ]);
    }

    public function test_rejects_malformed_schema_invalid_grading_unsafe_file_rule_and_legacy_advanced_fields(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        foreach ([
            ['question_type' => 'long_text', 'prompt' => 'Essay', 'answer_schema' => ['min_length' => 2, 'max_length' => 10000], 'sequence' => 1],
            ['question_type' => 'long_text', 'prompt' => 'Essay', 'grading_rule' => ['mode' => 'auto'], 'sequence' => 1],
            ['question_type' => 'file_response', 'prompt' => 'Upload', 'answer_schema' => ['allowed_file_categories' => ['pdf', 'zip'], 'max_file_size_bytes' => 26214400, 'max_files' => 1], 'sequence' => 1],
            ['question_type' => 'short_text', 'prompt' => 'Legacy', 'answer_schema' => ['min_length' => 1], 'sequence' => 1],
        ] as $question) {
            $this->withHeaders($this->headers($teacher, $school))->postJson('/api/v1/questionnaires', [
                'title' => 'Invalid quiz',
                'questions' => [$question],
            ])->assertUnprocessable();
        }

        $this->assertSame(0, Questionnaire::query()->count());
    }

    public function test_rejects_cross_tenant_authoring_context_before_persistence(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $this->withHeaders($this->headers($teacher, $otherSchool))->postJson('/api/v1/questionnaires', [
            'title' => 'Cross tenant quiz',
            'questions' => [
                ['question_type' => 'long_text', 'prompt' => 'Essay', 'sequence' => 1],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Questionnaire::query()->count());
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
