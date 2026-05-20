<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentGradeSelfViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_lists_only_own_grades(): void
    {
        [$school, $studentUser, $student, $teacher, $period] = $this->context();
        $grade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('data.0.id', $grade->uuid)
            ->assertJsonPath('meta.total', 1);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $studentUser, $student, $teacher, $period];
    }
}
