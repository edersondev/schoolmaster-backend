<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_context_failures_and_missing_permissions_are_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $inactiveSchool = School::factory()->inactive()->create();
        $teacher = $this->createTeacher($school);
        $platformUser = $this->createPlatformUser();

        $this->withToken($this->bearerTokenFor($platformUser))
            ->getJson('/api/v1/teacher-content')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/teacher-content')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($platformUser))
            ->withHeader('X-School-Id', $inactiveSchool->uuid)
            ->getJson('/api/v1/teacher-content')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($platformUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-content')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
