<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicPeriodDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_academic_period(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['academic_periods.view', 'academic_periods.manage']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-06-30']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/academic-periods/{$period->uuid}", ['name' => 'Updated Term'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Term');
    }
}
