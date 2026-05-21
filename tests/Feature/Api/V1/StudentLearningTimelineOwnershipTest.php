<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentLearningTimelineOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_learning_sets_assigned_to_own_active_profile(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $year = AcademicYear::query()->create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);
        $period = AcademicPeriod::query()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'active',
        ]);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $otherStudent = StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => User::factory()->create(['school_id' => $school->id])->id, 'status' => 'active']);
        $ownSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $student);
        $otherSet = TeacherWorkflowFactory::learningSet($school, $teacher, $period, $otherStudent);

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/learning-sets?academic_period_id='.$period->uuid)
            ->assertOk()
            ->assertJsonFragment(['id' => $ownSet->uuid])
            ->assertJsonMissing(['id' => $otherSet->uuid]);
    }
}
