<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountReactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_reactivate_eligible_inactive_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'School User',
        ]);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'inactive',
        ]);
        $target->roles()->attach($role);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
                'reason' => 'Support verified access',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_reactivation_rejects_inactive_school_dependency(): void
    {
        $school = School::factory()->create(['status' => 'inactive']);
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'inactive',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertUnauthorized();
    }

    public function test_reactivation_rejects_soft_deleted_user_without_disclosure(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'inactive',
        ]);
        $targetId = $target->uuid;
        $target->delete();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$targetId}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_reactivation_rejects_unresolved_invitation_setup(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'invited',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');
    }

    public function test_reactivation_rejects_user_without_active_role_dependency(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'inactive',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');
    }
}
