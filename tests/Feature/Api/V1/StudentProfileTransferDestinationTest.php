<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTransferDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_can_link_authorized_destination_profile_without_copying_history(): void
    {
        $sourceSchool = School::factory()->create();
        $destinationSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($sourceSchool, ['student_transfers.manage']);
        $destinationRole = $this->createSchoolAdmin($destinationSchool, ['student_transfers.manage'])->roles()->first();
        $admin->roles()->attach($destinationRole);
        $sourceProfile = StudentEnrollmentFactory::profile($sourceSchool, User::factory()->create(['school_id' => $sourceSchool->id]));
        $destinationProfile = StudentEnrollmentFactory::profile($destinationSchool, User::factory()->create(['school_id' => $destinationSchool->id]));

        $this->withToken($this->bearerTokenFor($admin->refresh()))
            ->withHeader('X-School-Id', $sourceSchool->uuid)
            ->postJson('/api/v1/student-profiles/'.$sourceProfile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'Linked destination profile.',
                'destination_school_id' => $destinationSchool->uuid,
                'destination_student_profile_id' => $destinationProfile->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('data.transfer.destination_school_id', $destinationSchool->uuid)
            ->assertJsonPath('data.transfer.destination_student_profile_id', $destinationProfile->uuid);
    }
}
