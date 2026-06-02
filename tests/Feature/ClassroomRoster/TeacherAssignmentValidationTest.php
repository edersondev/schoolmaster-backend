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

final class TeacherAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_duplicate_assignment_and_teacher_without_compatible_role(): void
    {
        [$school, $period, $admin, $classSection] = $this->context();
        $teacher = $this->createTeacher($school);
        $nonTeacher = $this->createSchoolAdmin($school, []);
        TeacherAssignment::factory()->forClassSection($school, $classSection, $teacher, $admin)->create();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSection->uuid,
                'teacher_user_id' => $teacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertConflict();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSection->uuid,
                'teacher_user_id' => $nonTeacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertUnprocessable();
    }

    public function test_deactivation_requires_reason_and_valid_date_order(): void
    {
        [$school, , $admin, $classSection] = $this->context();
        $teacher = $this->createTeacher($school);
        $assignment = TeacherAssignment::factory()->forClassSection($school, $classSection, $teacher, $admin)->create(['effective_start_date' => '2026-05-30']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/teacher-assignments/'.$assignment->uuid.'/status', [
                'status' => 'inactive',
                'effective_end_date' => '2026-05-29',
                'reason' => 'Ended',
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/teacher-assignments/'.$assignment->uuid.'/status', [
                'status' => 'inactive',
                'effective_end_date' => '2026-05-30',
            ])
            ->assertUnprocessable();
    }

    public function test_list_rejects_cross_tenant_academic_period_filter(): void
    {
        [$school, , $admin] = $this->context();
        $otherSchool = School::factory()->create();
        $otherYear = AcademicYear::query()->create([
            'school_id' => $otherSchool->id,
            'name' => '2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);
        $otherPeriod = AcademicPeriod::query()->create([
            'school_id' => $otherSchool->id,
            'academic_year_id' => $otherYear->id,
            'name' => 'Other Term',
            'sequence' => 1,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-assignments?academicPeriodId='.$otherPeriod->uuid)
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.academicPeriodId.0', 'The academic period was not found in the resolved school.');
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
