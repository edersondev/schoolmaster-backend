<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningSetLegacyAssignmentCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_direct_assignments_remain_readable_but_cannot_be_newly_written(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $learningSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);

        $this->withHeaders($this->headers($teacher, $school))
            ->getJson("/api/v1/learning-sets/{$learningSet->uuid}")
            ->assertOk()
            ->assertJsonPath('data.assignments.0.assignment_mode', 'legacy_direct')
            ->assertJsonPath('data.assignments.0.student_profile_id', $student->uuid);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['student_profile_ids' => [$student->uuid]])
            ->assertUnprocessable();
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
