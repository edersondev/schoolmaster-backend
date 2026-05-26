<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actor_without_lifecycle_permission_is_forbidden(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view']);
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'denied'])
            ->assertForbidden();
    }
}
