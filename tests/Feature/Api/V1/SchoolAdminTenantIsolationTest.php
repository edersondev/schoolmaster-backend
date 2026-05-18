<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolAdminTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_mismatched_inactive_and_unauthorized_tenant_contexts_are_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $inactiveSchool = School::factory()->inactive()->create();
        $schoolToken = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $platformToken = $this->bearerTokenFor($this->createPlatformUser());

        $this->withToken($platformToken)->getJson('/api/v1/academic-years')->assertForbidden();

        $this->withToken($schoolToken)
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($platformToken)
            ->withHeader('X-School-Id', $inactiveSchool->uuid)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($platformToken)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }
}
