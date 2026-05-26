<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_lifecycle_happy_path_from_update_to_restore(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.manage', 'users.lifecycle']);
        $user = User::factory()->create(['school_id' => $school->id, 'full_name' => 'Original']);
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$user->uuid}", ['full_name' => 'Updated'])
            ->assertOk();

        $payload = ['effective_at' => '2026-05-26', 'reason' => 'happy path'];

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", $payload)
            ->assertOk();

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)
            ->deleteJson("/api/v1/users/{$user->uuid}", $payload)
            ->assertOk();

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/restore", $payload)
            ->assertOk();
    }
}
