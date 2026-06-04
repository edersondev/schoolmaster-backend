<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherMaterialsContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_documents_teacher_material_operations(): void
    {
        $contract = file_get_contents(base_path('specs/specs/001-schoolmaster-platform/contracts/openapi.yaml'));

        foreach ([
            'getTeacherContent',
            'updateTeacherContent',
            'updateTeacherContentStatus',
            'deleteTeacherContent',
            'restoreTeacherContent',
            'downloadTeacherContent',
            'getQuestionnaire',
            'updateQuestionnaire',
            'updateQuestionnaireStatus',
            'deleteQuestionnaire',
            'restoreQuestionnaire',
        ] as $operationId) {
            $this->assertStringContainsString("operationId: $operationId", $contract);
        }
    }

    public function test_teacher_content_operations_return_documented_envelopes(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)
            ->getJson("/api/v1/teacher-content/{$content->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $content->uuid)
            ->assertJsonStructure(['data' => ['id', 'school_id', 'owner_user_id', 'title', 'content_type', 'file_size_bytes', 'scan_status', 'status'], 'meta']);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withHeaders($headers)
            ->getJson("/api/v1/teacher-content/{$content->uuid}/download")
            ->assertOk()
            ->assertJsonStructure(['data' => ['content_item_id', 'file_name', 'content_type', 'expires_at', 'download_url'], 'meta']);

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/teacher-content/{$content->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->withHeaders($headers)
            ->postJson("/api/v1/teacher-content/{$content->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_questionnaire_operations_return_documented_envelopes(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $teacher);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)
            ->getJson("/api/v1/questionnaires/{$questionnaire->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $questionnaire->uuid)
            ->assertJsonStructure(['data' => ['id', 'school_id', 'owner_user_id', 'title', 'status', 'questions'], 'meta']);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}", ['title' => 'Updated Quiz'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Quiz');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/questionnaires/{$questionnaire->uuid}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/questionnaires/{$questionnaire->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->withHeaders($headers)
            ->postJson("/api/v1/questionnaires/{$questionnaire->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
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
