<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cross_tenant_lifecycle_target_is_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.lifecycle']);
        $user = User::factory()->create(['school_id' => $otherSchool->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'denied'])
            ->assertNotFound();
    }
}
