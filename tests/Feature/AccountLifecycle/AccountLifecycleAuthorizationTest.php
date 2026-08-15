<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Permission;
use App\Models\School;
use App\Models\User;
use App\Policies\AccountLifecyclePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountLifecycleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_account_admin_cannot_manage_school_scoped_account(): void
    {
        $school = School::factory()->create();
        $platformAdmin = $this->createPlatformUser(['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($platformAdmin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-lock", [
                'reason' => 'Not allowed',
            ])
            ->assertForbidden();
    }

    public function test_policy_requires_the_active_permission_in_the_exact_scope(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, []);
        $role = $actor->roles()->firstOrFail();
        $platformPermission = Permission::query()->firstOrCreate(
            ['code' => 'account_lifecycle.manage', 'scope' => 'platform'],
            ['name' => 'Manage platform lifecycle', 'status' => 'active'],
        );
        $role->permissions()->attach($platformPermission);

        $this->assertFalse(app(AccountLifecyclePolicy::class)->manage($actor->refresh(), 'school', $school));

        $schoolPermission = Permission::query()->firstOrCreate(
            ['code' => 'account_lifecycle.manage', 'scope' => 'school'],
            ['name' => 'Manage school lifecycle', 'status' => 'inactive'],
        );
        $role->permissions()->attach($schoolPermission);

        $this->assertFalse(app(AccountLifecyclePolicy::class)->manage($actor->refresh(), 'school', $school));

        $schoolPermission->update(['status' => 'active']);

        $this->assertTrue(app(AccountLifecyclePolicy::class)->manage($actor->refresh(), 'school', $school));
    }

    public function test_system_administrator_has_master_access_but_cannot_target_self(): void
    {
        $school = School::factory()->create();
        $master = $this->createSystemAdministrator();
        $target = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $policy = app(AccountLifecyclePolicy::class);

        $this->assertTrue($policy->manage($master, 'school', $school, $target));
        $this->assertFalse($policy->manage($master, 'platform', null, $master));
    }

    public function test_actor_cannot_lock_their_own_account(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$actor->uuid}/account-lock", ['reason' => 'Self lock'])
            ->assertForbidden();
    }

    public function test_unknown_and_opposite_school_targets_have_identical_non_enumerating_outcomes(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $oppositeTarget = User::factory()->create(['school_id' => $otherSchool->id]);
        $token = $this->bearerTokenFor($actor);

        $opposite = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$oppositeTarget->uuid}/account-lock");
        $unknown = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users/00000000-0000-4000-8000-000000000000/account-lock');

        $opposite->assertNotFound();
        $unknown->assertNotFound();
        $this->assertSame($unknown->json('error.code'), $opposite->json('error.code'));
    }
}
