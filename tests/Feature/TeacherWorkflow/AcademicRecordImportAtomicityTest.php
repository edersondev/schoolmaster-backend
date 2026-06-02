<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordImportAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_import_commits_no_rows_when_any_row_is_invalid(): void
    {
        [$school, $admin, $student, $period] = $this->context();
        [, , $crossTenantStudent] = $this->context();
        $secondStudent = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', [
                'rows' => [
                    ['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 88],
                    ['student_profile_id' => $secondStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 90],
                    ['student_profile_id' => $crossTenantStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 92],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('grade_records', 0);
        $this->assertDatabaseHas('import_runs', ['school_id' => $school->id, 'status' => 'rejected', 'accepted_row_count' => 0, 'rejected_row_count' => 3]);
    }

    public function test_attendance_import_commits_no_rows_when_any_row_duplicates_another_row(): void
    {
        [$school, $admin, $student, $period] = $this->context();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/attendance/imports', [
                'rows' => [
                    ['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'attendance_date' => '2026-02-01', 'attendance_status' => 'present'],
                    ['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'attendance_date' => '2026-02-01', 'attendance_status' => 'late'],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => fake()->unique()->word(), 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $admin, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
