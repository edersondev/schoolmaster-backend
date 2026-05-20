<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GradeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_and_list_grades(): void
    {
        [$school, $teacher, $period, $student] = $this->context();

        $created = $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/grades', [
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 88,
                'grade_label' => 'B',
            ])
            ->assertCreated()
            ->assertJsonPath('data.grade_value', 88)
            ->json('data');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/grades')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_grade_creation_rejects_invalid_values_and_closed_periods(): void
    {
        [$school, $teacher, $period, $student] = $this->context(['period_status' => 'closed']);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/grades', [
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 101,
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/grades', [
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 90,
            ])
            ->assertUnprocessable();
    }

    private function context(array $options = []): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
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
            'end_date' => '2026-03-31',
            'status' => $options['period_status'] ?? 'active',
        ]);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $studentUser->id,
            'status' => 'active',
            'current_academic_year_id' => $year->id,
        ]);

        return [$school, $teacher, $period, $student];
    }
}
