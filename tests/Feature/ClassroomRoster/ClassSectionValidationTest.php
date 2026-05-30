<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassSectionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_unsupported_metadata_fields(): void
    {
        [$school, $period, $admin] = $this->context();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'BIO-1',
                'name' => 'Biology 1',
                'course' => ['code' => 'BIO', 'description' => 'Not approved'],
            ])
            ->assertUnprocessable();
    }

    public function test_rejects_duplicate_same_school_period_code(): void
    {
        [$school, $period, $admin] = $this->context();
        ClassSection::factory()->forSchoolPeriod($school, $period, $admin)->create(['code' => 'DUP-1']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'DUP-1',
                'name' => 'Duplicate',
            ])
            ->assertConflict();
    }

    public function test_rejects_inactive_academic_period_and_unsupported_queries(): void
    {
        [$school, $period, $admin] = $this->context(['status' => 'inactive']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'HIS-1',
                'name' => 'History 1',
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/class-sections?include=memberships&per_page=101')
            ->assertUnprocessable();
    }

    public function test_inactivation_requires_reason_and_reactivation_is_not_supported(): void
    {
        [$school, $period, $admin] = $this->context();
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $admin)->create();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/class-sections/'.$classSection->uuid.'/status', [
                'status' => 'inactive',
                'effective_at' => '2026-05-30',
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/class-sections/'.$classSection->uuid.'/status', [
                'status' => 'active',
                'effective_at' => '2026-05-30',
                'reason' => 'Reactivate',
            ])
            ->assertUnprocessable();
    }

    private function context(array $periodOverrides = []): array
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
        $period = AcademicPeriod::query()->create($periodOverrides + [
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        return [$school, $period, $admin];
    }
}
