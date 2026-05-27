<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicYearLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_deactivate_academic_year_without_active_periods(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['academic_years.view', 'academic_years.lifecycle']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/academic-years/{$year->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'closed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
