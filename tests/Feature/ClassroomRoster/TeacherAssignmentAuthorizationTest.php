<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\TeacherAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_read_only_own_active_assignment(): void
    {
        [$school, , $admin, $classSection] = $this->context();
        $teacher = $this->createTeacher($school);
        $otherTeacher = $this->createTeacher($school);
        $assignment = TeacherAssignment::factory()->forClassSection($school, $classSection, $teacher, $admin)->create();
        $otherAssignment = TeacherAssignment::factory()->forClassSection($school, $classSection, $otherTeacher, $admin)->create();

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-assignments/'.$assignment->uuid)
            ->assertOk();

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-assignments/'.$otherAssignment->uuid)
            ->assertForbidden();
    }

    public function test_non_admin_cannot_create_teacher_assignment(): void
    {
        [$school, $period, , $classSection] = $this->context();
        $teacher = $this->createTeacher($school);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSection->uuid,
                'teacher_user_id' => $teacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertForbidden();
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $admin)->create();

        return [$school, $period, $admin, $classSection];
    }
}
