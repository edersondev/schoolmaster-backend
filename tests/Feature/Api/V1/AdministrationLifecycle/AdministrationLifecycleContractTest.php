<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_response_uses_documented_success_shape(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.lifecycle']);
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'contract'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['resource_type', 'resource_id', 'action', 'status', 'lifecycle_history'], 'meta']);
    }
}
