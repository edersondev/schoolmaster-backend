<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_list_academic_year(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years', [
                'name' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ])
            ->assertCreated()
            ->assertJsonPath('data.school_id', $school->uuid)
            ->json('data');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/academic-years')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_academic_year_rejects_invalid_date_range(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years', [
                'name' => 'Invalid',
                'start_date' => '2026-12-31',
                'end_date' => '2026-01-01',
            ])
            ->assertUnprocessable();
    }

    public function test_academic_year_rejects_non_contract_date_format(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years', [
                'name' => 'Invalid',
                'start_date' => 'January 1 2026',
                'end_date' => 'December 31 2026',
            ])
            ->assertUnprocessable();
    }
}
