<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_list_academic_period(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-periods', [
                'academic_year_id' => $year->uuid,
                'name' => 'Term 1',
                'sequence' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
            ])
            ->assertCreated()
            ->assertJsonPath('data.academic_year_id', $year->uuid)
            ->json('data');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/academic-periods?academic_year_id='.$year->uuid)
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_academic_period_rejects_out_of_range_and_duplicate_sequence(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-periods', [
                'academic_year_id' => $year->uuid,
                'name' => 'Invalid Term',
                'sequence' => 1,
                'start_date' => '2025-12-01',
                'end_date' => '2026-03-31',
            ])
            ->assertUnprocessable();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-periods', [
                'academic_year_id' => $year->uuid,
                'name' => 'Term 1',
                'sequence' => 1,
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-periods', [
                'academic_year_id' => $year->uuid,
                'name' => 'Duplicate Term',
                'sequence' => 1,
                'start_date' => '2026-04-01',
                'end_date' => '2026-06-30',
            ])
            ->assertUnprocessable();
    }
}
