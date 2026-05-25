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

final class StudentProfileTransferTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_does_not_copy_source_school_academic_records_to_destination(): void
    {
        $sourceSchool = School::factory()->create();
        $destinationSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($sourceSchool, ['student_transfers.manage']);
        $sourceProfile = StudentEnrollmentFactory::profile($sourceSchool, User::factory()->create(['school_id' => $sourceSchool->id]));
        $year = AcademicYear::query()->create(['school_id' => $sourceSchool->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $period = AcademicPeriod::query()->create(['school_id' => $sourceSchool->id, 'academic_year_id' => $year->id, 'name' => 'Term 1', 'sequence' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'active']);
        GradeRecord::query()->create(['school_id' => $sourceSchool->id, 'student_profile_id' => $sourceProfile->id, 'academic_period_id' => $period->id, 'recorded_by_user_id' => $admin->id, 'grade_value' => 91, 'recorded_at' => now()]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $sourceSchool->uuid)
            ->postJson('/api/v1/student-profiles/'.$sourceProfile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'No copy.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('grade_records', ['school_id' => $sourceSchool->id, 'student_profile_id' => $sourceProfile->id]);
        $this->assertDatabaseMissing('grade_records', ['school_id' => $destinationSchool->id]);
    }
}
