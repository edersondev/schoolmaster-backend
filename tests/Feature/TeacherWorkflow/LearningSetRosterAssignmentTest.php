<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\LearningSetAssignment;
use App\Models\RosterMembership;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LearningSetRosterAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_same_school_roster_assignment_write_succeeds_and_direct_student_write_is_rejected(): void
    {
        Storage::fake('teacher_content');

        [$school, $teacher, $period, $studentUser, $student, $learningSet] = $this->context();
        $secondStudentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $secondStudent = StudentProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $secondStudentUser->id,
            'status' => 'active',
        ]);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        Storage::disk('teacher_content')->put($content->storage_path, 'file');
        TeacherWorkflowFactory::learningSetEntry($school, $learningSet, 'content_item', $content->id, 1);
        $classSection = $this->activeClassSection($school, $period, [$student, $secondStudent], $teacher);

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['roster_assignment' => ['class_section_id' => $classSection->uuid]])
            ->assertOk()
            ->assertJsonCount(2, 'data.assignments')
            ->assertJsonPath('data.assignments.0.assignment_mode', 'roster')
            ->assertJsonPath('data.assignments.0.class_section_id', $classSection->uuid)
            ->assertJsonPath('data.assignments.0.student_profile_id', null);

        $this->assertSame(
            2,
            LearningSetAssignment::query()
                ->where('learning_set_id', $learningSet->id)
                ->where('assignment_mode', 'roster')
                ->where('status', 'active')
                ->count(),
        );

        $this->withHeaders($this->headers($studentUser, $school))
            ->getJson('/api/v1/student/learning-sets?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('data.0.id', $learningSet->uuid)
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($this->headers($secondStudentUser, $school))
            ->getJson('/api/v1/student/learning-sets?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonPath('data.0.id', $learningSet->uuid)
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($this->headers($secondStudentUser, $school))
            ->get('/api/v1/student/teacher-content/'.$content->uuid.'/download')
            ->assertOk();

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['student_profile_ids' => [$student->uuid]])
            ->assertUnprocessable();
    }

    public function test_invalid_roster_or_content_dependency_does_not_partially_update(): void
    {
        [$school, $teacher, $period, , $student, $learningSet] = $this->context();
        $inactiveClassSection = ClassSection::factory()->forSchoolPeriod($school, $period, $teacher)->inactive()->create();
        $uncleanContent = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);
        $originalTitle = $learningSet->title;

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", ['roster_assignment' => ['class_section_id' => $inactiveClassSection->uuid]])
            ->assertUnprocessable();

        $this->withHeaders($this->headers($teacher, $school))
            ->patchJson("/api/v1/learning-sets/{$learningSet->uuid}", [
                'title' => 'Should Not Persist',
                'entries' => [
                    ['entry_type' => 'content_item', 'entry_reference_id' => $uncleanContent->uuid, 'sequence' => 1],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('learning_sets', ['id' => $learningSet->id, 'title' => $originalTitle]);
    }

    /**
     * @param  array<int, StudentProfile>  $students
     */
    private function activeClassSection(School $school, AcademicPeriod $period, array $students, User $teacher): ClassSection
    {
        $classSection = ClassSection::factory()->forSchoolPeriod($school, $period, $teacher)->create();
        foreach ($students as $student) {
            RosterMembership::factory()->forClassSection($school, $classSection, $student, $teacher)->create();
        }

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
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active', 'current_academic_year_id' => $year->id]);

        return [$school, $teacher, $period, $studentUser, $student, TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student)];
    }

    private function headers(User $user, School $school): array
    {
        return ['Authorization' => 'Bearer '.$this->bearerTokenFor($user), 'X-School-Id' => $school->uuid];
    }
}
