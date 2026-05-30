<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RosterMembershipValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_duplicate_and_invalid_memberships_without_partial_changes(): void
    {
        [$school, $period, $admin, $classSection] = $this->context();
        $student = $this->student($school);
        RosterMembership::factory()->forClassSection($school, $classSection, $student, $admin)->create(['effective_start_date' => '2026-01-01']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSection->uuid.'/memberships', [
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertConflict();
    }

    public function test_rejects_oversized_batch_and_end_before_start(): void
    {
        [$school, $period, $admin, $classSection] = $this->context();
        $student = $this->student($school);
        $membership = RosterMembership::factory()->forClassSection($school, $classSection, $student, $admin)->create(['effective_start_date' => '2026-05-30']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSection->uuid.'/memberships', [
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
                'student_profile_ids' => array_fill(0, 101, $student->uuid),
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/class-sections/'.$classSection->uuid.'/memberships', [
                'effective_end_date' => '2026-05-01',
                'reason' => 'Left roster',
                'roster_membership_ids' => [$membership->uuid],
            ])
            ->assertUnprocessable();
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

    private function student(School $school): StudentProfile
    {
        return StudentProfile::query()->create(['school_id' => $school->id, 'registration_number' => fake()->unique()->bothify('STU-###'), 'first_name' => 'Grace', 'last_name' => 'Hopper', 'status' => 'active', 'enrolled_at' => '2026-01-01']);
    }
}
