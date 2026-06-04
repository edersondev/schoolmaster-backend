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

final class AcademicRecordContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_documents_academic_record_operations(): void
    {
        $contract = file_get_contents(base_path('specs/api/openapi.yaml'));

        foreach (['getGrade', 'correctGrade', 'updateGradeStatus', 'deleteGrade', 'restoreGrade', 'getAttendance', 'correctAttendance', 'updateAttendanceStatus', 'deleteAttendance', 'restoreAttendance'] as $operationId) {
            $this->assertStringContainsString("operationId: $operationId", $contract);
        }
    }

    public function test_grade_and_attendance_operations_return_documented_envelopes(): void
    {
        [$school, $teacher, , $student, $period] = $this->context();
        $grade = GradeRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'original_recorded_by_user_id' => $teacher->id,
            'grade_value' => 80,
            'grade_label' => 'B',
            'status' => 'active',
            'recorded_at' => now(),
        ]);
        $attendance = AttendanceRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'original_recorded_by_user_id' => $teacher->id,
            'attendance_date' => '2026-02-01',
            'attendance_status' => 'present',
            'status' => 'active',
        ]);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)->getJson("/api/v1/grades/{$grade->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'school_id', 'student_profile_id', 'academic_period_id', 'recorded_by_user_id', 'grade_value', 'current_value', 'correction_history', 'status'], 'meta']);

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 88, 'grade_label' => 'B+', 'correction_reason' => 'Corrected after rubric review.'])
            ->assertOk()
            ->assertJsonPath('data.current_value', 88)
            ->assertJsonCount(1, 'data.correction_history');

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/status", ['status' => 'inactive'])->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->withHeaders($headers)->deleteJson("/api/v1/grades/{$grade->uuid}")->assertOk()->assertJsonPath('data.status', 'deleted');
        $this->withHeaders($headers)->postJson("/api/v1/grades/{$grade->uuid}/restore")->assertOk()->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($headers)->getJson("/api/v1/attendance/{$attendance->uuid}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'attendance_status', 'current_value', 'correction_history', 'status'], 'meta']);

        $this->withHeaders($headers)->patchJson("/api/v1/attendance/{$attendance->uuid}/correction", ['attendance_status' => 'late', 'correction_reason' => 'Corrected after teacher review.'])
            ->assertOk()
            ->assertJsonPath('data.current_value', 'late')
            ->assertJsonCount(1, 'data.correction_history');

        $this->withHeaders($headers)->patchJson("/api/v1/attendance/{$attendance->uuid}/status", ['status' => 'inactive'])->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->withHeaders($headers)->deleteJson("/api/v1/attendance/{$attendance->uuid}")->assertOk()->assertJsonPath('data.status', 'deleted');
        $this->withHeaders($headers)->postJson("/api/v1/attendance/{$attendance->uuid}/restore")->assertOk()->assertJsonPath('data.status', 'inactive');
    }

    private function context(string $periodStatus = 'active'): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => $periodStatus]);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $studentUser, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
