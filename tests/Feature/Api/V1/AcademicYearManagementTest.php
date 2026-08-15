<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicYear;
use App\Models\School;
use Database\Factories\AdministrationLifecycleFactory;
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

    public function test_academic_year_list_validates_documented_filters(): void
    {
        $school = School::factory()->create();
        $request = $this->withToken($this->bearerTokenFor($this->createSchoolAdmin($school)))
            ->withHeader('X-School-Id', $school->uuid);

        foreach ([
            ['status' => 'archived'],
            ['date_from' => '2026-01-01'],
            ['date_to' => '2026-12-31'],
            ['date_from' => 'not-a-date', 'date_to' => '2026-12-31'],
            ['date_from' => '2026-12-31', 'date_to' => '2026-01-01'],
            ['unknown' => 'value'],
        ] as $query) {
            $request->getJson('/api/v1/academic-years?'.http_build_query($query))
                ->assertUnprocessable();
        }

        foreach (['planned', 'active', 'closed', 'inactive'] as $status) {
            $request->getJson('/api/v1/academic-years?status='.$status)->assertOk();
        }
    }

    public function test_academic_year_list_filters_by_name_status_and_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $matching = AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'North Campus 2026',
            'status' => 'planned',
        ]);
        AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'South Campus 2026',
            'status' => 'planned',
        ]);
        AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'North Campus 2025',
            'status' => 'closed',
        ]);
        AdministrationLifecycleFactory::academicYear($otherSchool, [
            'name' => 'North Campus 2026',
            'status' => 'planned',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/academic-years?name=north%20campus&status=planned')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->uuid);
    }

    public function test_academic_year_list_uses_inclusive_date_range_overlap(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $overlapping = collect([
            ['name' => 'Encloses range', 'start_date' => '2025-01-01', 'end_date' => '2027-12-31'],
            ['name' => 'Inside range', 'start_date' => '2026-05-01', 'end_date' => '2026-05-31'],
            ['name' => 'Touches start', 'start_date' => '2025-06-01', 'end_date' => '2026-04-01'],
            ['name' => 'Touches end', 'start_date' => '2026-06-30', 'end_date' => '2027-06-30'],
        ])->map(fn (array $data): AcademicYear => AdministrationLifecycleFactory::academicYear($school, $data));
        $before = AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'Before range',
            'start_date' => '2025-01-01',
            'end_date' => '2026-03-31',
        ]);
        $after = AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'After range',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $response = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/academic-years?date_from=2026-04-01&date_to=2026-06-30')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        foreach ($overlapping as $academicYear) {
            $response->assertJsonFragment(['id' => $academicYear->uuid]);
        }

        $response
            ->assertJsonMissing(['id' => $before->uuid])
            ->assertJsonMissing(['id' => $after->uuid]);
    }

    public function test_academic_year_list_combines_all_filters_with_and_semantics(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $matching = AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'Primary 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'Primary 2026',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
        ]);
        AdministrationLifecycleFactory::academicYear($school, [
            'name' => 'Primary 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'closed',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/academic-years?name=primary&date_from=2026-05-01&date_to=2026-05-31&status=active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->uuid);
    }
}
