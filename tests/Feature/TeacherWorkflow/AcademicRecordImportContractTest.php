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

final class AcademicRecordImportContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_documents_grade_and_attendance_import_operations(): void
    {
        $contract = file_get_contents(base_path('specs/specs/001-schoolmaster-platform/contracts/openapi.yaml'));

        foreach (['importGrades', 'importAttendance', 'GradeImportRequest', 'AttendanceImportRequest', 'ImportRun'] as $contractToken) {
            $this->assertStringContainsString($contractToken, $contract);
        }
    }

    public function test_grade_and_attendance_imports_return_documented_import_run_envelopes(): void
    {
        [$school, $admin, $student, $period] = $this->context();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', [
                'rows' => [[
                    'student_profile_id' => $student->uuid,
                    'academic_period_id' => $period->uuid,
                    'grade_value' => 91,
                    'grade_label' => 'A-',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.import_type', 'grade')
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.row_count', 1)
            ->assertJsonPath('data.accepted_row_count', 1)
            ->assertJsonPath('data.rejected_row_count', 0)
            ->assertJsonStructure(['data' => ['id', 'school_id', 'actor_user_id', 'import_type', 'row_count', 'accepted_row_count', 'rejected_row_count', 'status', 'error_summary'], 'meta']);

        [$otherSchool, $otherAdmin, $otherStudent, $otherPeriod] = $this->context();

        $this->withHeaders($this->headers($otherAdmin, $otherSchool))
            ->postJson('/api/v1/attendance/imports', [
                'rows' => [[
                    'student_profile_id' => $otherStudent->uuid,
                    'academic_period_id' => $otherPeriod->uuid,
                    'attendance_date' => '2026-02-01',
                    'attendance_status' => 'present',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.import_type', 'attendance')
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.accepted_row_count', 1);
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

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
