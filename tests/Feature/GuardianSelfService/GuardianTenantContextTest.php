<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\School;
use App\Models\User;

final class GuardianTenantContextTest extends GuardianSelfServiceTestCase
{
    public function test_guardian_routes_reject_missing_mismatched_and_unauthorized_tenant_context(): void
    {
        [$school, , , $guardianUser, $student, $period] = $this->guardianContext();
        $platformUser = User::factory()->create(['school_id' => null]);
        $otherSchool = School::factory()->create();

        $this->withToken($this->bearerTokenFor($platformUser))
            ->getJson('/api/v1/guardian/students')
            ->assertForbidden();

        $this->withToken($this->bearerTokenFor($guardianUser))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/guardian/students')
            ->assertForbidden();

        foreach ([
            "/api/v1/guardian/students/{$student->uuid}",
            "/api/v1/guardian/students/{$student->uuid}/academics?academic_period_id={$period->uuid}",
            "/api/v1/guardian/students/{$student->uuid}/contacts",
        ] as $uri) {
            $this->withHeaders($this->headers($guardianUser, $school))
                ->getJson($uri)
                ->assertSuccessful();
        }
    }
}
