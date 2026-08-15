<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvitedUserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_user_update_cannot_activate_invited_user(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $target = User::factory()->create(['school_id' => $school->id, 'status' => 'invited']);

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$target->uuid}", ['status' => 'active'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertSame('invited', $target->refresh()->status);
    }

    public function test_invited_user_cannot_be_reactivated_through_recovery(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Invited User',
        ]);
        $target = User::factory()->create(['school_id' => $school->id, 'status' => 'invited']);
        $target->roles()->attach($role);

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertConflict();

        $this->assertSame('invited', $target->refresh()->status);
    }
}
