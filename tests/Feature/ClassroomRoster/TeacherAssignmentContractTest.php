<?php

declare(strict_types=1);

namespace Tests\Feature\ClassroomRoster;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class TeacherAssignmentContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_assignment_routes_are_registered_in_the_backend(): void
    {
        foreach ([
            'api.v1.teacher-assignments.index',
            'api.v1.teacher-assignments.store',
            'api.v1.teacher-assignments.show',
            'api.v1.teacher-assignments.status.update',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), sprintf('Expected route [%s] to be registered.', $routeName));
        }
    }

    public function test_create_teacher_assignment_returns_documented_success_envelope(): void
    {
        [$school, $period, $admin, $classSection] = $this->context();
        $teacher = $this->createTeacher($school);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/teacher-assignments', [
                'class_section_id' => $classSection->uuid,
                'teacher_user_id' => $teacher->uuid,
                'academic_period_id' => $period->uuid,
                'effective_start_date' => '2026-05-30',
            ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'class_section_id', 'teacher_user_id', 'status'], 'meta'])
            ->assertJsonPath('data.status', 'active');
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['classroom_rosters.manage']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $admin)->create();

        return [$school, $period, $admin, $classSection];
    }
}
