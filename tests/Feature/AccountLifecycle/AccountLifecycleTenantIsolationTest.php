<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Permission;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountLifecycleTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_school_actor_with_exact_permission_can_read_lock_state(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertOk()
            ->assertJsonPath('data.user_id', $target->uuid);
    }

    public function test_inactive_permission_and_missing_school_context_are_denied(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        Permission::query()
            ->where('code', 'account_lifecycle.manage')
            ->where('scope', 'school')
            ->update(['status' => 'inactive']);
        $target = User::factory()->create(['school_id' => $school->id]);
        $token = $this->bearerTokenFor($actor);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertForbidden();
        $this->withToken($token)
            ->getJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertForbidden();
    }

    public function test_platform_actor_can_manage_only_platform_target_without_school_header(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $target = User::factory()->create(['school_id' => null]);

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertOk();
    }

    public function test_system_administrator_can_use_platform_and_exact_school_modes(): void
    {
        $school = School::factory()->create();
        $master = $this->createSystemAdministrator();
        $platformTarget = User::factory()->create(['school_id' => null]);
        $schoolTarget = User::factory()->create(['school_id' => $school->id]);
        $token = $this->bearerTokenFor($master);

        $this->withToken($token)
            ->getJson("/api/v1/users/{$platformTarget->uuid}/account-lock")
            ->assertOk();
        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$schoolTarget->uuid}/account-lock")
            ->assertOk();
    }

    public function test_cross_school_unknown_and_soft_deleted_targets_are_not_found(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $crossSchool = User::factory()->create(['school_id' => $otherSchool->id]);
        $deleted = User::factory()->create(['school_id' => $school->id]);
        $deletedUuid = $deleted->uuid;
        $deleted->delete();
        $token = $this->bearerTokenFor($actor);

        foreach ([$crossSchool->uuid, $deletedUuid, '00000000-0000-4000-8000-000000000000'] as $uuid) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson("/api/v1/users/{$uuid}/account-lock")
                ->assertNotFound()
                ->assertJsonPath('error.code', 'not_found');
        }
    }

    public function test_password_delivery_returns_tenant_mismatch_for_missing_inactive_or_mismatched_context(): void
    {
        $school = School::factory()->create();
        $inactiveSchool = School::factory()->create(['status' => School::STATUS_INACTIVE]);
        $otherSchool = School::factory()->create();
        $target = User::factory()->create(['school_id' => $school->id]);
        $schoolAdmin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $master = $this->createSystemAdministrator();

        $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($schoolAdmin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($master))
            ->withHeader('X-School-Id', $inactiveSchool->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
