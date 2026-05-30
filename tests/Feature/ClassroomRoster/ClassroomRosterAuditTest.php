<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassroomRosterAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_foundation_writes_tenant_safe_audit_events(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $teacher = $this->createTeacher($school);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'registration_number' => 'S-200', 'first_name' => 'Katherine', 'last_name' => 'Johnson', 'status' => 'active', 'enrolled_at' => '2026-01-01']);

        $classSectionId = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', ['academic_period_id' => $period->uuid, 'code' => 'AUD-1', 'name' => 'Audit'])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSectionId.'/memberships', ['academic_period_id' => $period->uuid, 'effective_start_date' => '2026-05-30', 'student_profile_ids' => [$student->uuid]])
            ->assertCreated();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', ['class_section_id' => $classSectionId, 'teacher_user_id' => $teacher->uuid, 'academic_period_id' => $period->uuid, 'effective_start_date' => '2026-05-30'])
            ->assertCreated();

        $events = AuditEvent::query()
            ->where('school_id', $school->id)
            ->whereIn('event_type', [
                'classroom_roster.class_section.created',
                'classroom_roster.roster_memberships.added',
                'classroom_roster.teacher_assignment.created',
            ])
            ->get();

        $this->assertCount(3, $events);

        foreach ($events as $event) {
            $this->assertSame($admin->id, $event->actor_user_id);
            $this->assertArrayNotHasKey('student_name', $event->tenant_safe_metadata ?? []);
            $this->assertArrayNotHasKey('teacher_email', $event->tenant_safe_metadata ?? []);
        }
    }
}
