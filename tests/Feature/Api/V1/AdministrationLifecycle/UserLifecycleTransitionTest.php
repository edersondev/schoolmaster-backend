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
}
