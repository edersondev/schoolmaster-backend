<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicYearDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_academic_year(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['academic_years.view', 'academic_years.manage']);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'planned',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/academic-years/{$year->uuid}", ['name' => '2026 Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', '2026 Updated');
    }
}
