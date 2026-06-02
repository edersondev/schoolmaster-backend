<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\LearningSet;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningSetLifecycleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_to_inactive_and_activation_dependency_conflict_are_enforced(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $learningSet = LearningSet::query()->create([
            'school_id' => $school->id,
            'owner_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'title' => 'Empty Set',
            'status' => 'inactive',
        ]);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}/status", ['status' => 'active'])
            ->assertConflict();

        $this->withHeaders($this->headers($teacher, $school))
            ->deleteJson("/api/v1/learning-sets/{$learningSet->uuid}")
            ->assertOk()
            ->assertJsonPath('data.status', 'deleted');

        $this->withHeaders($this->headers($teacher, $school))
            ->postJson("/api/v1/learning-sets/{$learningSet->uuid}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'teacher_workflow.lifecycle',
            'affected_resource_type' => LearningSet::class,
            'affected_resource_id' => $learningSet->uuid,
        ]);
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
