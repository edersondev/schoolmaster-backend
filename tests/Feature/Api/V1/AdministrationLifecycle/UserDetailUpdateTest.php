<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'full_name' => 'Old User']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->uuid);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$user->uuid}", ['full_name' => 'New User'])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'New User');
    }

    public function test_user_detail_rejects_cross_tenant_access(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view']);
        $user = User::factory()->create(['school_id' => $otherSchool->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$user->uuid}")
            ->assertNotFound();
    }

    public function test_platform_user_can_view_only_platform_user_without_school_context(): void
    {
        $school = School::factory()->create();
        $actor = $this->createPlatformUser(['schools.view']);
        $platformTarget = User::factory()->create(['school_id' => null]);
        $schoolTarget = User::factory()->create(['school_id' => $school->id]);
        $token = $this->bearerTokenFor($actor);

        $this->withToken($token)
            ->getJson("/api/v1/users/{$platformTarget->uuid}")
            ->assertOk()
            ->assertJsonPath('data.id', $platformTarget->uuid);
        $this->withToken($token)
            ->getJson("/api/v1/users/{$schoolTarget->uuid}")
            ->assertNotFound();
    }

    public function test_school_detail_unknown_and_opposite_mode_targets_are_indistinguishable(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.view']);
        $platformTarget = User::factory()->create(['school_id' => null]);
        $token = $this->bearerTokenFor($actor);

        $opposite = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$platformTarget->uuid}");
        $unknown = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users/00000000-0000-4000-8000-000000000000');

        $opposite->assertNotFound();
        $unknown->assertNotFound();
        $this->assertSame($unknown->json('error.code'), $opposite->json('error.code'));
    }

    public function test_lifecycle_permission_does_not_grant_user_detail_access(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$target->uuid}")
            ->assertForbidden();
    }

    public function test_user_update_rejects_duplicate_email_before_persistence(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'email' => 'target@example.test']);
        User::factory()->create(['school_id' => $school->id, 'email' => 'taken@example.test']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$user->uuid}", ['email' => 'taken@example.test'])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'target@example.test',
        ]);
    }

    public function test_platform_user_update_accepts_only_active_platform_roles(): void
    {
        $actor = $this->createPlatformUser(['schools.view', 'schools.manage']);
        $target = User::factory()->create(['school_id' => null]);
        $platformRole = Role::query()->create([
            'school_id' => null,
            'scope' => 'platform',
            'name' => 'Platform Operations',
            'status' => 'active',
        ]);
        $schoolRole = Role::query()->create([
            'school_id' => School::factory()->create()->id,
            'scope' => 'school',
            'name' => 'School Operations',
            'status' => 'active',
        ]);
        $token = $this->bearerTokenFor($actor);

        $this->withToken($token)
            ->patchJson("/api/v1/users/{$target->uuid}", [
                'role_ids' => [$platformRole->uuid],
            ])
            ->assertOk();

        $this->assertTrue($target->roles()->whereKey($platformRole->id)->exists());

        $this->withToken($token)
            ->patchJson("/api/v1/users/{$target->uuid}", [
                'role_ids' => [$schoolRole->uuid],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertTrue($target->roles()->whereKey($platformRole->id)->exists());
        $this->assertFalse($target->roles()->whereKey($schoolRole->id)->exists());
    }

    public function test_non_master_platform_user_cannot_assign_roles_to_self(): void
    {
        $actor = $this->createPlatformUser(['schools.view', 'schools.manage']);
        $originalRole = $actor->roles()->firstOrFail();
        $systemAdministratorRole = Role::query()->create([
            'school_id' => null,
            'scope' => 'platform',
            'name' => 'System Administrator',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($actor))
            ->patchJson("/api/v1/users/{$actor->uuid}", [
                'role_ids' => [$systemAdministratorRole->uuid],
            ])
            ->assertForbidden();

        $this->assertTrue($actor->roles()->whereKey($originalRole->id)->exists());
        $this->assertFalse($actor->roles()->whereKey($systemAdministratorRole->id)->exists());
        $this->assertFalse($actor->refresh()->isSystemAdministrator());
    }

    public function test_non_master_platform_user_cannot_assign_master_role_to_another_user(): void
    {
        $actor = $this->createPlatformUser(['schools.view', 'schools.manage']);
        $target = User::factory()->create(['school_id' => null]);
        $systemAdministratorRole = Role::query()->create([
            'school_id' => null,
            'scope' => 'platform',
            'name' => 'System Administrator',
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($actor))
            ->patchJson("/api/v1/users/{$target->uuid}", [
                'role_ids' => [$systemAdministratorRole->uuid],
            ])
            ->assertForbidden();

        $this->assertFalse($target->roles()->whereKey($systemAdministratorRole->id)->exists());
        $this->assertFalse($target->refresh()->isSystemAdministrator());
    }

    public function test_system_administrator_can_assign_master_role_to_platform_user(): void
    {
        $actor = $this->createSystemAdministrator();
        $systemAdministratorRole = $actor->roles()->where('name', 'System Administrator')->firstOrFail();
        $target = User::factory()->create(['school_id' => null]);

        $this->withToken($this->bearerTokenFor($actor))
            ->patchJson("/api/v1/users/{$target->uuid}", [
                'role_ids' => [$systemAdministratorRole->uuid],
            ])
            ->assertOk();

        $this->assertTrue($target->refresh()->isSystemAdministrator());
    }
}
