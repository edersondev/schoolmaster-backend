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

final class TeacherWorkflowTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_inactive_unauthorized_and_cross_tenant_contexts_are_rejected_across_operations(): void
    {
        [$school, $teacher, $admin, $student, $period] = $this->context();
        [$otherSchool, $otherTeacher, , $otherStudent, $otherPeriod] = $this->context();
        $inactiveSchool = School::factory()->create(['status' => 'inactive']);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $grade = TeacherWorkflowFactory::grade($school, $teacher, $period, $student);
        $otherGrade = TeacherWorkflowFactory::grade($otherSchool, $otherTeacher, $otherPeriod, $otherStudent);
        $headers = $this->headers($teacher, $school);
        $platform = $this->createPlatformUser();

        $this->withToken($this->bearerTokenFor($platform))->getJson("/api/v1/teacher-content/{$content->uuid}")->assertForbidden();
        $this->withHeaders(['Authorization' => 'Bearer '.$this->bearerTokenFor($teacher), 'X-School-Id' => $inactiveSchool->uuid])->getJson("/api/v1/teacher-content/{$content->uuid}")->assertForbidden();
        $this->withHeaders($headers)->getJson("/api/v1/grades/{$otherGrade->uuid}")->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}/download")->assertOk();
        $this->withHeaders($headers)->patchJson("/api/v1/grades/{$grade->uuid}/correction", ['grade_value' => 89, 'correction_reason' => 'Same tenant correction only.'])->assertOk();

        $this->withHeaders($this->headers($admin, $school))
            ->postJson('/api/v1/grades/imports', ['rows' => [['student_profile_id' => $otherStudent->uuid, 'academic_period_id' => $period->uuid, 'grade_value' => 75]]])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('grade_records', ['school_id' => $school->id, 'student_profile_id' => $otherStudent->id]);
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $admin = $this->createSchoolAdmin($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => fake()->unique()->year(), 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => fake()->unique()->word(), 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);

        return [$school, $teacher, $admin, $student, $period];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
