<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkAcademicPeriodLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_academic_period_lifecycle_success(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['academic_periods.lifecycle']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-periods/bulk-lifecycle', [
                'resource_type' => 'academic_periods',
                'action' => 'deactivate',
                'record_ids' => [$period->uuid],
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk();
    }
}
