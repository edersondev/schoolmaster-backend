<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkAcademicYearLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_academic_year_lifecycle_success(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['academic_years.lifecycle']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years/bulk-lifecycle', [
                'resource_type' => 'academic_years',
                'action' => 'deactivate',
                'record_ids' => [$year->uuid],
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk();
    }
}
