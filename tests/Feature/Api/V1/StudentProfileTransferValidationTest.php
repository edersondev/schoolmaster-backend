<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTransferValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_rejects_inactive_source_and_missing_destination_permission(): void
    {
        $school = School::factory()->create();
        $destinationSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_transfers.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['status' => 'inactive']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles/'.$profile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'Invalid inactive source.',
            ])
            ->assertConflict();

        $activeProfile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]), ['registration_number' => 'STU-TRANSFER']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles/'.$activeProfile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'Missing destination permission.',
                'destination_school_id' => $destinationSchool->uuid,
            ])
            ->assertForbidden();
    }

    public function test_transfer_does_not_leak_destination_school_or_profile_existence_without_permission(): void
    {
        $school = School::factory()->create();
        $destinationSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_transfers.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));
        $destinationProfile = StudentEnrollmentFactory::profile($destinationSchool, User::factory()->create(['school_id' => $destinationSchool->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles/'.$profile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'No destination permission.',
                'destination_school_id' => $destinationSchool->uuid,
                'destination_student_profile_id' => $destinationProfile->uuid,
            ])
            ->assertForbidden();
    }
}
