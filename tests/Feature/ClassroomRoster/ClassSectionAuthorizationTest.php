<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassSectionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_roster_admin_cannot_create_class_section(): void
    {
        [$school, $period] = $this->activePeriod();
        $teacher = $this->createTeacher($school);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'SCI-1',
                'name' => 'Science 1',
            ])
            ->assertForbidden();
    }

    public function test_class_section_from_another_school_is_not_found(): void
    {
        [$school, $period] = $this->activePeriod();
        [$otherSchool] = $this->activePeriod();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $otherAdmin = $this->createSchoolAdmin($otherSchool, ['classroom_rosters.manage']);

        $classSection = ClassSection::factory()
            ->forSchoolPeriod($school, $period, $admin)
            ->create(['code' => 'ENG-1']);

        $this->withToken($this->bearerTokenFor($otherAdmin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/class-sections/'.$classSection->uuid)
            ->assertNotFound();
    }

    public function test_platform_user_without_school_context_is_rejected_before_roster_lookup(): void
    {
        $platformUser = $this->createPlatformUser();

        $this->withToken($this->bearerTokenFor($platformUser))
            ->getJson('/api/v1/class-sections')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }

    private function activePeriod(): array
    {
        $school = School::factory()->create();
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
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$school, $period];
    }
}
