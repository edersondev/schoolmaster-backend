<?php

declare(strict_types=1);

namespace Tests\Unit\GuardianSelfService;

use App\DTOs\GuardianSelfService\GuardianAcademicSummaryQuery;
use App\DTOs\GuardianSelfService\GuardianActorContext;
use App\DTOs\GuardianSelfService\GuardianStudentTarget;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\CorrectionRecord;
use App\Models\GradeRecord;
use App\Models\Guardian;
use App\Models\GuardianUserLink;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\GuardianSelfService\GuardianAcademicSummaryService;
use App\Services\GuardianSelfService\GuardianVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuardianAcademicSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_summary_redacts_correction_history(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian', 'relationship_type' => 'guardian', 'status' => 'active']);
        $link = GuardianUserLink::query()->create(['school_id' => $school->id, 'guardian_id' => $guardian->id, 'user_id' => $user->id, 'status' => 'active']);
        $student = StudentProfile::query()->create(['school_id' => $school->id, 'status' => 'active']);
        $year = AcademicYear::query()->create(['school_id' => $school->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        $grade = GradeRecord::query()->create(['school_id' => $school->id, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $user->id, 'grade_value' => 70, 'status' => 'active']);
        CorrectionRecord::query()->create(['school_id' => $school->id, 'target_record_type' => 'grade', 'target_record_id' => $grade->uuid, 'student_profile_id' => $student->id, 'academic_period_id' => $period->id, 'actor_user_id' => $user->id, 'correction_reason' => 'Private reason', 'new_value' => ['grade_value' => 75]]);

        $summary = (new GuardianAcademicSummaryService(new GuardianVisibilityService))->summary(new GuardianAcademicSummaryQuery(
            new GuardianStudentTarget(new GuardianActorContext($user, $school, $guardian, $link), $student, 'guardian'),
            $period,
        ));

        $this->assertArrayNotHasKey('correction_history', $summary['grade_summary']);
        $this->assertSame(70.0, $summary['grade_summary']['average']);
    }
}
