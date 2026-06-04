<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowAuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_story_audit_events_are_tenant_safe_for_lifecycle_download_correction_and_imports(): void
    {
        [$school, $teacher, $admin, $student, $period] = $this->context();
        $headers = $this->headers($teacher, $school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $pendingContent = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);
        $grade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        $closedPeriod = $this->period($school, 'closed');
        $closedGrade = GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $closedPeriod->id, 'recorded_by_user_id' => $teacher->id, 'grade_value' => 75, 'status' => 'active', 'recorded_at' => now()]);
        [, , , $otherStudent] = $this->context();

        $this->withHeaders($headers)->patchJson("/api/v1/teacher-content/{$content->uuid}", ['title' => 'Audit title'])->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}/download")->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$pendingContent->uuid}/download")->assertForbidden();
        $this->withHeaders($headers)->patchJson("/api/v1/teacher-content/{$pendingContent->uuid}/status", ['status' => 'inactive'])->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/teacher-content/{$pendingContent->uuid}/status", ['status' => 'active'])->assertConflict();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 96, 'correction_reason' => 'Audit accepted correction.'])->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$closedGrade->uuid}/correction", ['grade_value' => 80, 'correction_reason' => 'Audit denied correction.'])->assertForbidden();
        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'active'])->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 89]]])->assertCreated();
        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => $otherStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 90]]])->assertUnprocessable();

        foreach ([
            ['teacher_workflow.lifecycle', 'success'],
            ['teacher_workflow.download', 'success'],
            ['teacher_workflow.download', 'denied'],
            ['teacher_workflow.correction', 'success'],
            ['teacher_workflow.correction', 'denied'],
            ['teacher_workflow.import', 'success'],
            ['teacher_workflow.import', 'rejected'],
            ['teacher_workflow.conflict', 'conflict'],
        ] as [$eventType, $outcome]) {
            $this->assertDatabaseHas('audit_events', ['event_type' => $eventType, 'outcome' => $outcome, 'school_id' => $school->id]);
        }

        foreach (AuditEvent::query()->whereIn('event_type', AuditEvent::TEACHER_WORKFLOW_EVENT_TYPES)->get() as $event) {
            $metadata = json_encode($event->tenant_safe_metadata, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('request_payload', $metadata);
            $this->assertStringNotContainsString('storage_path', $metadata);
            $this->assertStringNotContainsString($otherStudent->uuid, $metadata);
        }
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $period = $this->period($school);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $admin, $student, $period];
    }

    private function period(School $school, string $status = 'active'): AcademicPeriod
    {
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        return AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => fake()->unique()->word(), 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => $status]);
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
