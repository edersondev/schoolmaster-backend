<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordImportAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_and_rejected_imports_are_audited_without_full_payload_storage(): void
    {
        [$school, $admin, $student, $period] = $this->context();
        [, , $otherStudent] = $this->context();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 88]]])
            ->assertCreated();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => $otherStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 90]]])
            ->assertUnprocessable();

        $this->assertDatabaseHas('audit_events', ['event_type' => 'teacher_workflow.import', 'outcome' => 'success', 'actor_user_id' => $admin->id, 'school_id' => $school->id]);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'teacher_workflow.import', 'outcome' => 'rejected', 'actor_user_id' => $admin->id, 'school_id' => $school->id]);

        foreach (AuditEvent::query()->where('event_type', 'teacher_workflow.import')->get() as $event) {
            $metadata = json_encode($event->tenant_safe_metadata, JSON_THROW_ON_ERROR);
            $this->assertStringContainsString('row_count', $metadata);
            $this->assertStringNotContainsString('request_payload', $metadata);
            $this->assertStringNotContainsString($otherStudent->uuid, $metadata);
        }
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => fake()->unique()->word(), 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $admin, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
