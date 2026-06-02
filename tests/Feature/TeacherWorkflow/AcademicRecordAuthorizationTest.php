<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AcademicRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_admin_can_correct_open_period_record_while_non_owner_and_student_are_denied(): void
    {
        [$school, $teacher, $studentUser, $student, $period] = $this->context();
        $admin = $this->createSchoolAdmin($school);
        $nonOwner = $this->createTeacher($school);
        $grade = $this->grade($school, $teacher, $student, $period);

        $this->withHeaders($this->headers($teacher, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90, 'correction_reason' => 'Corrected by original teacher.'])->assertOk();
        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 91, 'correction_reason' => 'Corrected by school administrator.'])->assertOk();
        $this->withHeaders($this->headers($nonOwner, $school))->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 92, 'correction_reason' => 'Unauthorized teacher correction.'])->assertForbidden();
        $this->withHeaders($this->headers($studentUser, $school))->getJson("/api/v1/grades/{$grade->uuid}")->assertForbidden();
    }

    public function test_closed_period_teacher_is_denied_and_cross_tenant_record_is_not_found(): void
    {
        [$school, $teacher, , $student, $period] = $this->context('closed');
        $grade = $this->grade($school, $teacher, $student, $period);
        [$otherSchool, $otherTeacher, , $otherStudent, $otherPeriod] = $this->context();
        $otherGrade = $this->grade($otherSchool, $otherTeacher, $otherStudent, $otherPeriod);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 90, 'correction_reason' => 'Closed period teacher denial.'])
            ->assertForbidden();

        $this->withHeaders($this->headers($teacher, $school))
            ->getJson("/api/v1/grades/{$otherGrade->uuid}")
            ->assertNotFound();
    }

    private function grade(School $school, User $teacher, StudentProfile $student, AcademicPeriod $period): GradeRecord
    {
        return GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'original_recorded_by_user_id' => $teacher->id, 'grade_value' => 80, 'status' => 'active', 'recorded_at' => now()]);
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
