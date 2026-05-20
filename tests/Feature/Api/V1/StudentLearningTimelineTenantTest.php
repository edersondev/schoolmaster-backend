<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentLearningTimelineTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_and_mismatched_tenant_context_are_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $platformUser = $this->createPlatformUser();
        $studentUser = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($platformUser))
            ->getJson('/api/v1/student/learning-sets')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($studentUser))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/student/learning-sets')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
