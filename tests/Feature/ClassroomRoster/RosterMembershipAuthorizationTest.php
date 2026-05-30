<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RosterMembershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_writes_are_school_admin_only(): void
    {
        [$school, $period, $classSection] = $this->context();
        $teacher = $this->createTeacher($school);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'registration_number' => 'S-1', 'first_name' => 'A', 'last_name' => 'B', 'status' => 'active', 'enrolled_at' => '2026-01-01']);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSection->uuid.'/memberships', [
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
                'student_profile_ids' => [$student->uuid],
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

        return [$school, $period, $classSection];
    }
}
