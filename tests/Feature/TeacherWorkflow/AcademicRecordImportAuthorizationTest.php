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

final class AcademicRecordImportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_school_administrators_can_import_academic_records(): void
    {
        [$school, $admin, $student, $period] = $this->context();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $guardian = User::factory()->create(['school_id' => $school->id]);
        $nonAdmin = $this->createSchoolAdmin($school, ['users.view']);
        $platform = $this->createPlatformUser();
        $payload = $this->gradePayload($student, $period);

        $this->withHeaders($this->headers($admin, $school))->postJson('/api/v1/grades/imports', $payload)->assertCreated();
        $this->withHeaders($this->headers($teacher, $school))->postJson('/api/v1/attendance/imports', $this->attendancePayload($student, $period))->assertForbidden();
        $this->withHeaders($this->headers($studentUser, $school))->postJson('/api/v1/grades/imports', $payload)->assertForbidden();
        $this->withHeaders($this->headers($guardian, $school))->postJson('/api/v1/grades/imports', $payload)->assertForbidden();
        $this->withHeaders($this->headers($nonAdmin, $school))->postJson('/api/v1/grades/imports', $payload)->assertForbidden();
        $this->withHeaders($this->headers($platform, $school))->postJson('/api/v1/grades/imports', $payload)->assertForbidden();
    }

    public function test_cross_tenant_import_target_is_rejected_without_row_creation(): void
    {
        [$school, $admin, , $period] = $this->context();
        [, , $otherStudent] = $this->context();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', $this->gradePayload($otherStudent, $period))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseCount('grade_records', 0);
        $this->assertDatabaseHas('import_runs', ['school_id' => $school->id, 'import_type' => 'grade', 'status' => 'rejected']);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $admin, $student, $period];
    }

    private function gradePayload(StudentProfile $student, AcademicPeriod $period): array
    {
        return ['rows' => [['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 88]]];
    }

    private function attendancePayload(StudentProfile $student, AcademicPeriod $period): array
    {
        return ['rows' => [['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'attendance_date' => '2026-02-01', 'attendance_status' => 'present']]];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
