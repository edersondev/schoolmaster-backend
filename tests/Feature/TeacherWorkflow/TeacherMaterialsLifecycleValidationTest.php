<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherMaterialsLifecycleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_restore_and_validation_rules_for_content(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}", ['unsupported' => true])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'deleted'])
            ->assertUnprocessable();

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/teacher-content/{$content->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'active'])
            ->assertConflict();

        $this->withHeaders($headers)
            ->postJson("/api/v1/teacher-content/{$content->uuid}/restore", ['unsupported' => true])
            ->assertUnprocessable();

        $this->withHeaders($headers)
            ->postJson("/api/v1/teacher-content/{$content->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_pending_scan_content_cannot_be_activated(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending', 'status' => 'inactive']);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'active'])
            ->assertConflict();
    }

    public function test_questionnaire_question_validation_rejects_unsupported_shape(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $teacher);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", [
                'questions' => [
                    ['question_type' => 'multiple_choice', 'prompt' => 'Pick one', 'options' => ['A'], 'sequence' => 1],
                ],
            ])
            ->assertUnprocessable();
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
