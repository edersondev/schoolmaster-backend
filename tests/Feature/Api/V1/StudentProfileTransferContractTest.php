<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileTransferContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_returns_documented_result_shape(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_transfers.manage']);
        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/student-profiles/'.$profile->uuid.'/transfer', [
                'effective_at' => '2026-04-01',
                'reason' => 'Transferred.',
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['student_profile', 'transfer', 'enrollment_history'], 'meta']);
    }
}
