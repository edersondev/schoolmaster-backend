<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AuditEvent;
use App\Models\ClassSection;
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

    public function test_roster_foundation_audits_conflict_forbidden_and_tenant_failures(): void
    {
        [$school, $period, $admin] = $this->context();
        [$otherSchool] = $this->context();
        $teacher = $this->createTeacher($school);
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $admin)->create(['code' => 'AUD-FAIL']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/class-sections')
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSection->uuid,
                'teacher_user_id' => $teacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'AUD-FAIL',
                'name' => 'Duplicate Audit',
            ])
            ->assertConflict();

        $tenantFailure = AuditEvent::query()
            ->where('outcome', 'tenant_mismatch')
            ->where('actor_user_id', $admin->id)
            ->first();

        $forbiddenFailure = AuditEvent::query()
            ->where('outcome', 'forbidden')
            ->where('actor_user_id', $teacher->id)
            ->first();

        $conflictFailure = AuditEvent::query()
            ->where('outcome', 'conflict')
            ->where('actor_user_id', $admin->id)
            ->first();

        $this->assertNotNull($tenantFailure);
        $this->assertNotNull($forbiddenFailure);
        $this->assertNotNull($conflictFailure);
        $this->assertSame($admin->id, $tenantFailure->actor_user_id);
        $this->assertSame($teacher->id, $forbiddenFailure->actor_user_id);
        $this->assertSame($admin->id, $conflictFailure->actor_user_id);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => fake()->unique()->year(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$school, $period, $admin];
    }
}
