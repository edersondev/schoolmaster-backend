<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\GradeRecord;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_workflow_lifecycle_happy_paths_within_one_school_context(): void
    {
        [$school, $teacher, $admin, $student, $period] = $this->context();
        $headers = $this->headers($teacher, $school);
        $adminHeaders = $this->headers($admin, $school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $questionnaire = TeacherWorkflowFactory::questionnaire($school, $teacher);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $classSection = $this->activeClassSection($school, $period, $student, $teacher);
        $grade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        $attendance = TeacherWorkflowFactory::attendance($school, $teacher, $period, $student);
        $importStudent = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}")->assertOk()->assertJsonPath('data.id', $content->uuid);
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}/download")->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/questionnaires/{$questionnaire->uuid}")->assertOk()->assertJsonPath('data.id', $questionnaire->uuid);

        $this->withHeaders($headers)
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['roster_assignment' => ['class_section_id' => $classSection->uuid]])
            ->assertOk()
            ->assertJsonPath('data.assignments.0.assignment_mode', 'roster');

        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 97, 'correction_reason' => 'End to end grade correction.'])->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/attendance/{$attendance->uuid}/correction", ['attendance_status' => 'late', 'correction_reason' => 'End to end attendance correction.'])->assertOk();

        $this->withHeaders($adminHeaders)
            ->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => $importStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 88]]])
            ->assertCreated()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('grade_records', ['school_id' => $school->id, 'student_profile_id' => $importStudent->id, 'grade_value' => 88]);
    }

    private function activeClassSection(School $school, AcademicPeriod $period, StudentProfile $student, User $teacher): ClassSection
    {
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $teacher)->create();
        RosterMembership::factory()->forClassSection($school, $classSection, $student, $teacher)->create();
        TeacherAssignment::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $teacher->id,
            'updated_by_user_id' => $teacher->id,
        ]);

        return $classSection;
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active', 'current_academic_year_id' => $year->id]);

        return [$school, $teacher, $admin, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
