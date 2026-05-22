<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\GradeRecord;
use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileHistoryPreservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_changes_preserve_existing_academic_records(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);
        $teacher = $this->createTeacher($school);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $profile->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $teacher->id, 'grade_value' => 90, 'recorded_at' => now()]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/student-profiles/'.$profile->uuid.'/status', [
                'status' => 'inactive',
                'effective_at' => '2026-03-01',
                'reason' => 'Inactive.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('grade_records', ['student_profile_id' => $profile->id, 'grade_value' => 90]);
    }
}
