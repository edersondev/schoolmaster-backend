<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_deactivate_user_and_history_is_written(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.lifecycle']);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", [
                'effective_at' => '2026-05-26',
                'reason' => 'left school',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('lifecycle_histories', ['resource_uuid' => $user->uuid, 'operation' => 'deactivated']);
    }

    public function test_school_admin_cannot_activate_invited_user_before_password_setup(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.lifecycle']);
        $user = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'invited',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/activate", [
                'effective_at' => '2026-08-12',
                'reason' => 'Attempted setup bypass',
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');

        $this->assertSame('invited', $user->refresh()->status);
        $this->assertDatabaseMissing('lifecycle_histories', [
            'resource_uuid' => $user->uuid,
            'operation' => 'activated',
        ]);
    }

    public function test_platform_admin_can_apply_all_user_lifecycle_transitions_without_school_context(): void
    {
        $admin = $this->createPlatformUser(['schools.manage']);
        $user = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $token = $this->bearerTokenFor($admin);
        $payload = [
            'effective_at' => now()->toDateString(),
            'reason' => 'Platform user lifecycle review',
        ];

        $this->withToken($token)
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withToken($token)
            ->postJson("/api/v1/users/{$user->uuid}/activate", $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withToken($token)
            ->deleteJson("/api/v1/users/{$user->uuid}", $payload)
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->withToken($token)
            ->postJson("/api/v1/users/{$user->uuid}/restore", $payload)
            ->assertOk();

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('lifecycle_histories', 4);
    }

    public function test_platform_mode_does_not_transition_school_owned_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createPlatformUser(['schools.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->postJson("/api/v1/users/{$user->uuid}/deactivate", [
                'effective_at' => now()->toDateString(),
                'reason' => 'Out-of-scope transition',
            ])
            ->assertNotFound();

        $this->assertSame('active', $user->refresh()->status);
        $this->assertDatabaseMissing('lifecycle_histories', [
            'resource_uuid' => $user->uuid,
        ]);
    }
}
