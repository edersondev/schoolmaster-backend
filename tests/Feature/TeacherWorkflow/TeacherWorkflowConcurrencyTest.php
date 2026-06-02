<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_conflicting_lifecycle_correction_download_and_import_writes_do_not_leave_partial_state(): void
    {
        [$school, $teacher, $admin, $student, $period] = $this->context();
        $headers = $this->headers($teacher, $school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);
        $grade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        $inactiveStudent = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'inactive']);

        $this->withHeaders($headers)->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'inactive'])->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/teacher-content/{$content->uuid}/status", ['status' => 'active'])->assertConflict();
        $this->assertDatabaseHas('teacher_content_items', ['id' => $content->id, 'status' => 'inactive', 'scan_status' => 'pending']);

        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}/download")->assertForbidden();

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 91, 'correction_reason' => 'First bounded correction.'])->assertOk();
        $this->withHeaders($headers)->deleteJson("/api/v1/grades/{$grade->uuid}")->assertOk();
        $this->withHeaders($headers)->deleteJson("/api/v1/grades/{$grade->uuid}")->assertConflict();
        $this->assertDatabaseHas('grade_records', ['id' => $grade->id, 'status' => 'deleted', 'grade_value' => 91]);

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => [
                ['student_profile_id' => $inactiveStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 80],
                ['student_profile_id' => $student->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 82],
            ]])
            ->assertUnprocessable();

        $this->assertDatabaseCount('grade_records', 1);
        $this->assertDatabaseHas('import_runs', ['school_id' => $school->id, 'status' => 'rejected', 'accepted_row_count' => 0]);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $admin, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
