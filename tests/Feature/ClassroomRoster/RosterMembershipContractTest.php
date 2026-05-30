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

final class RosterMembershipContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_contains_membership_operation_ids(): void
    {
        $contract = file_get_contents(base_path('specs/api/openapi.yaml'));

        foreach (['listClassSectionMemberships', 'batchAddClassSectionMemberships', 'batchEndClassSectionMemberships'] as $operationId) {
            $this->assertStringContainsString('operationId: '.$operationId, $contract);
        }
    }

    public function test_batch_add_memberships_returns_documented_result_envelope(): void
    {
        [$school, $period, $admin, $classSection] = $this->context();
        $student = $this->student($school);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections/'.$classSection->uuid.'/memberships', [
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
                'student_profile_ids' => [$student->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.affected_count', 1)
            ->assertJsonStructure(['data' => ['memberships' => [['id', 'status', 'effective_start_date']]], 'meta']);
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

    private function student(School $school, array $overrides = []): StudentProfile
    {
        return StudentProfile::query()->create($overrides + ['school_id' => $school->id, 'registration_number' => fake()->unique()->bothify('STU-###'), 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'status' => 'active', 'enrolled_at' => '2026-01-01']);
    }
}
