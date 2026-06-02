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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_success_and_rejection_are_audited_without_private_payloads(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $closedPeriod = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'closed']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $grade = GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $closedPeriod->id, 'recorded_by_user_id' => $teacher->id, 'original_recorded_by_user_id' => $teacher->id, 'grade_value' => 80, 'status' => 'active', 'recorded_at' => now()]);
        $admin = $this->createSchoolAdmin($school);

        $this->withHeaders($this->headers($teacher, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 83, 'correction_reason' => 'Teacher denied closed period.'])->assertForbidden();
        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 84, 'correction_reason' => 'Administrator accepted correction.'])->assertOk();

        $this->assertDatabaseHas('audit_events', ['event_type' => 'teacher_workflow.correction', 'outcome' => 'denied']);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'teacher_workflow.correction', 'outcome' => 'success']);

        foreach (AuditEvent::query()->where('event_type', 'teacher_workflow.correction')->get() as $event) {
            $metadata = json_encode($event->tenant_safe_metadata, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('request_payload', $metadata);
            $this->assertStringNotContainsString('Teacher denied closed period', $metadata);
        }
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
