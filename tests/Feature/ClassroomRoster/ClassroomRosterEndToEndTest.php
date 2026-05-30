<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassroomRosterEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_roster_add_membership_assign_teacher_and_isolate_other_school(): void
    {
        [$school, $period, $admin] = $this->context();
        [$otherSchool, , $otherAdmin] = $this->context();
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'registration_number' => 'S-100', 'first_name' => 'Alan', 'last_name' => 'Turing', 'status' => 'active', 'enrolled_at' => '2026-01-01']);
        $teacher = $this->createTeacher($school);

        $create = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'E2E-1',
                'name' => 'End to End',
            ])
            ->assertCreated();

        $classSectionId = $create->json('data.id');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSectionId.'/memberships', [
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.affected_count', 1);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSectionId,
                'teacher_user_id' => $teacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertCreated();

        $this->withToken($this->bearerTokenFor($otherAdmin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/class-sections/'.$classSectionId)
            ->assertNotFound();
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        return [$school, $period, $admin];
    }
}
