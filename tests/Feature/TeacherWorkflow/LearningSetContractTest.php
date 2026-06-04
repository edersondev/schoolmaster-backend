<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningSetContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_documents_learning_set_operations(): void
    {
        $contract = file_get_contents(base_path('specs/api/openapi.yaml'));

        foreach (['getLearningSet', 'updateLearningSet', 'updateLearningSetStatus', 'deleteLearningSet', 'restoreLearningSet'] as $operationId) {
            $this->assertStringContainsString("operationId: $operationId", $contract);
        }
    }

    public function test_learning_set_operations_return_documented_envelopes(): void
    {
        [$school, $teacher, $learningSet] = $this->context();
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)
            ->getJson("/api/v1/learning-sets/{$learningSet->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'school_id', 'owner_user_id', 'academic_period_id', 'title', 'status', 'entries', 'assignments'], 'meta']);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['title' => 'Updated Set'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Set');

        $this->withHeaders($headers)
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/learning-sets/{$learningSet->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->withHeaders($headers)
            ->postJson("/api/v1/learning-sets/{$learningSet->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $student = \App\Models\StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => \App\Models\User::factory()->create(['school_id' => $school->id])->id,
            'status' => 'active',
        ]);
        $year = \App\Models\AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = \App\Models\AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);

        return [$school, $teacher, $learningSet];
    }

    private function headers($user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
