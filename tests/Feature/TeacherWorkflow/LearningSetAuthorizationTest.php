<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningSetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_school_admin_can_access_learning_sets_while_non_owner_and_students_are_denied(): void
    {
        [$school, $teacher, $period, $student, $learningSet] = $this->context();
        $admin = $this->createSchoolAdmin($school);
        $nonOwner = $this->createTeacher($school);
        $studentUser = $student->user;

        $this->withHeaders($this->headers($teacher, $school))->getJson("/api/v1/learning-sets/{$learningSet->uuid}")->assertOk();
        $this->withHeaders($this->headers($admin, $school))->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['title' => 'Admin Edit'])->assertOk();
        $this->withHeaders($this->headers($nonOwner, $school))->getJson("/api/v1/learning-sets/{$learningSet->uuid}")->assertForbidden();
        $this->withHeaders($this->headers($studentUser, $school))->getJson("/api/v1/learning-sets/{$learningSet->uuid}")->assertForbidden();
    }

    public function test_inactive_teacher_assignment_blocks_roster_assignment_write(): void
    {
        [$school, $teacher, $period, $student, $learningSet] = $this->context();
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $teacher)->create();
        RosterMembership::factory()->forClassSection($school, $classSection, $student, $teacher)->create();
        TeacherAssignment::query()->create([
            'school_id' => $school->id,
            'class_section_id' => $classSection->id,
            'teacher_user_id' => $teacher->id,
            'academic_period_id' => $period->id,
            'status' => 'inactive',
            'effective_start_date' => '2026-01-01',
            'created_by_user_id' => $teacher->id,
            'updated_by_user_id' => $teacher->id,
        ]);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['roster_assignment' => ['class_section_id' => $classSection->uuid]])
            ->assertForbidden();
    }

    public function test_cross_tenant_learning_set_is_not_found(): void
    {
        [$school, $teacher] = $this->context();
        [$otherSchool, $otherTeacher, , $otherStudent, $otherLearningSet] = $this->context();

        $this->withHeaders($this->headers($teacher, $school))
            ->getJson("/api/v1/learning-sets/{$otherLearningSet->uuid}")
            ->assertNotFound();
    }

    private function context(): array
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active', 'current_academic_year_id' => $year->id]);

        return [$school, $teacher, $period, $student, TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student)];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
