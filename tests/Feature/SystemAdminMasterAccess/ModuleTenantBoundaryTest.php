<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\AcademicYear;
use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ModuleTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_academic_and_teacher_module_queries_stay_tenant_scoped(): void
    {
        $first = School::factory()->create();
        $second = School::factory()->create();
        $firstYear = AcademicYear::query()->create([
            'school_id' => $first->id,
            'name' => 'First Tenant Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $secondYear = AcademicYear::query()->create([
            'school_id' => $second->id,
            'name' => 'Second Tenant Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $firstTeacher = $this->createTeacher($first);
        $secondTeacher = $this->createTeacher($second);
        $firstContent = TeacherWorkflowFactory::cleanContent($first, $firstTeacher, ['title' => 'First Tenant Content']);
        $secondContent = TeacherWorkflowFactory::cleanContent($second, $secondTeacher, ['title' => 'Second Tenant Content']);
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)->withHeader('X-School-Id', $first->uuid)
            ->getJson('/api/v1/academic-years')
            ->assertOk()->assertJsonFragment(['id' => $firstYear->uuid])->assertJsonMissing(['id' => $secondYear->uuid]);

        $this->withToken($token)->withHeader('X-School-Id', $first->uuid)
            ->getJson('/api/v1/teacher-content')
            ->assertOk()->assertJsonFragment(['id' => $firstContent->uuid])->assertJsonMissing(['id' => $secondContent->uuid]);
    }
}
