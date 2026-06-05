<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

use App\Models\GuardianUserLink;
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

    public function test_platform_scoped_user_cannot_use_guardian_self_service_even_with_linked_record(): void
    {
        [$school, $admin, $guardian, , $student] = $this->guardianContext();
        $platformUser = User::factory()->create(['school_id' => null, 'status' => 'active']);

        GuardianUserLink::query()->create([
            'school_id' => $school->id,
            'guardian_id' => $guardian->id,
            'user_id' => $platformUser->id,
            'created_by_user_id' => $admin->id,
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($platformUser))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/guardian/students/{$student->uuid}")
            ->assertForbidden();
    }
}
