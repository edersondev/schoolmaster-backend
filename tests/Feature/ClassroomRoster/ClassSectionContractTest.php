<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassSectionContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_contains_class_section_operation_ids(): void
    {
        $contract = file_get_contents(base_path('specs/api/openapi.yaml'));

        foreach (['listClassSections', 'createClassSection', 'getClassSection', 'updateClassSection', 'updateClassSectionStatus'] as $operationId) {
            $this->assertStringContainsString('operationId: '.$operationId, $contract);
        }
    }

    public function test_create_class_section_returns_documented_success_envelope(): void
    {
        [$school, $period] = $this->activePeriod();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);

        $response = $this
            ->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/class-sections', [
                'academic_period_id' => $period->uuid,
                'code' => 'MATH-1',
                'name' => 'Mathematics 1',
                'course' => ['code' => 'MATH', 'name' => 'Mathematics'],
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'school_id', 'academic_period_id', 'code', 'name', 'status'],
                'meta',
            ])
            ->assertJsonPath('data.status', 'active');
    }

    private function activePeriod(): array
    {
        $school = School::factory()->create();
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
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
