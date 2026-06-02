<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClosedPeriodCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_correct_closed_period_grade_and_attendance(): void
    {
        [$school, $teacher, $student, $period] = $this->context();
        $admin = $this->createSchoolAdmin($school);
        $grade = GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'original_recorded_by_user_id' => $teacher->id, 'grade_value' => 70, 'status' => 'active', 'recorded_at' => now()]);
        $attendance = AttendanceRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'original_recorded_by_user_id' => $teacher->id, 'attendance_date' => '2026-02-01', 'attendance_status' => 'absent', 'status' => 'active']);

        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 75, 'correction_reason' => 'Closed period administrator correction.'])->assertOk()->assertJsonPath('data.original_value', 70);
        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/attendance/{$attendance->uuid}/correction", ['attendance_status' => 'excused', 'correction_reason' => 'Closed period administrator correction.'])->assertOk()->assertJsonPath('data.original_value', 'absent');

        $this->assertDatabaseCount('correction_records', 2);
        $this->assertDatabaseCount('audit_events', 2);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'closed']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
