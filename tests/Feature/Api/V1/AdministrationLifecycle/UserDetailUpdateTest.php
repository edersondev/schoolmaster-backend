<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

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
}
