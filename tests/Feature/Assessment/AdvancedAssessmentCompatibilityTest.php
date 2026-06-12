<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedAssessmentCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_questionnaire_authoring_still_accepts_existing_v1_types(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $this->withHeaders($this->headers($teacher, $school))->postJson('/api/v1/questionnaires', [
            'title' => 'Legacy quiz',
            'questions' => [
                ['question_type' => 'multiple_choice', 'prompt' => 'Pick', 'options' => ['A', 'B'], 'correct_answer' => 'A', 'sequence' => 1],
                ['question_type' => 'true_false', 'prompt' => 'Ready?', 'correct_answer' => 'true', 'sequence' => 2],
                ['question_type' => 'short_text', 'prompt' => 'Name', 'correct_answer' => 'Ada', 'sequence' => 3],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.questions.0.question_type', 'multiple_choice')
            ->assertJsonPath('data.questions.1.question_type', 'true_false')
            ->assertJsonPath('data.questions.2.question_type', 'short_text');
    }

    public function test_platform_user_still_has_no_advanced_assessment_detail_access(): void
    {
        $school = School::factory()->create();
        $platform = $this->createPlatformUser(['schools.view']);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearerTokenFor($platform), 'X-School-Id' => $school->uuid])
            ->getJson('/api/v1/questionnaire-responses')
            ->assertForbidden();
    }

    private function headers($user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
