<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentProfileStatusValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_rejects_transfer_status_and_cross_tenant_profiles(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['student_profiles.manage']);
        $otherProfile = StudentEnrollmentFactory::profile($otherSchool, User::factory()->create(['school_id' => $otherSchool->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/student-profiles/'.$otherProfile->uuid.'/status', [
                'status' => 'inactive',
                'effective_at' => '2026-03-01',
                'reason' => 'Invalid tenant.',
            ])
            ->assertNotFound();

        $profile = StudentEnrollmentFactory::profile($school, User::factory()->create(['school_id' => $school->id]));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson('/api/v1/student-profiles/'.$profile->uuid.'/status', [
                'status' => 'transferred',
                'effective_at' => '2026-03-01',
                'reason' => 'Must use transfer endpoint.',
            ])
            ->assertUnprocessable();
    }
}
