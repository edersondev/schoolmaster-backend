<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\ImportRun;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordImportValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_reject_non_json_oversized_malformed_and_undocumented_rows(): void
    {
        [$school, $admin, $student, $period] = $this->context();

        $this->withHeaders($this->headers($admin, $school))
            ->post('/api/v1/grades/imports', $this->gradePayload($student, $period))
            ->assertUnprocessable();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => array_fill(0, 501, ['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 75])])
            ->assertUnprocessable();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => ['not-a-row']])
            ->assertUnprocessable();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => [[
                'student_profile_id' => $student->uuid,
                'academic_period_id' => $period->uuid,
                'grade_value' => 80,
                'id' => 'f7d92353-7992-4b75-8d34-3d471d8c5d1b',
                'correction_reason' => 'Import cannot correct existing records.',
            ]]])
            ->assertUnprocessable();
    }

    public function test_imports_reject_duplicate_existing_inactive_cross_tenant_closed_period_and_unsupported_values_safely(): void
    {
        [$school, $admin, $student, $period] = $this->context();
        [$otherSchool, $otherAdmin, $otherStudent] = $this->context();
        $closedPeriod = $this->period($school, 'closed');
        $inactiveStudent = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'inactive']);
        GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $admin->id, 'original_recorded_by_user_id' => $admin->id, 'grade_value' => 80, 'status' => 'active', 'recorded_at' => now()]);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', $this->gradePayload($student, $period))
            ->assertUnprocessable();

        $rejected = ImportRun::query()->where('school_id', $school->id)->where('status', 'rejected')->latest('id')->firstOrFail();
        $this->assertSame('duplicate_existing_record', $rejected->error_summary[0]['code']);

        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', $this->gradePayload($inactiveStudent, $period))->assertUnprocessable();
        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', $this->gradePayload($otherStudent, $period))->assertUnprocessable();
        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', $this->gradePayload($student, $closedPeriod))->assertUnprocessable();

        $this->withHeaders($this->headers($otherAdmin, $otherSchool))
            ->postJson('/api/v1/attendance/imports', ['rows' => [['student_profile_id' => $otherStudent->uuid, 'academic_period_id' => $period->uuid, 'attendance_date' => '2026-02-01', 'attendance_status' => 'unknown']]])
            ->assertUnprocessable();

        $summary = json_encode(ImportRun::query()->where('status', 'rejected')->pluck('error_summary')->all(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($otherStudent->uuid, $summary);
    }

    public function test_attendance_import_rejects_duplicate_existing_records(): void
    {
        [$school, $admin, $student, $period] = $this->context();
        AttendanceRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $admin->id, 'original_recorded_by_user_id' => $admin->id, 'attendance_date' => '2026-02-01', 'attendance_status' => 'present', 'status' => 'active']);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/attendance/imports', ['rows' => [['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'attendance_date' => '2026-02-01', 'attendance_status' => 'late']]])
            ->assertUnprocessable();

        $this->assertDatabaseCount('attendance_records', 1);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $period = $this->period($school);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $admin, $student, $period];
    }

    private function period(School $school, string $status = 'active'): AcademicPeriod
    {
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);

        return AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => fake()->unique()->word(), 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => $status]);
    }

    private function gradePayload(StudentProfile $student, AcademicPeriod $period): array
    {
        return ['rows' => [['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 88]]];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
