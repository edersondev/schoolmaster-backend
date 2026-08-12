<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountInvitationCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_same_school_invitation_without_token_secret(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Invited Teacher',
        ]);
        User::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Invited User',
            'email' => 'invited@example.test',
            'status' => 'invited',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'school',
                'school_id' => $school->uuid,
                'full_name' => 'Invited User',
                'email' => 'invited@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.scope', 'school')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.token_hash');
    }

    public function test_platform_admin_can_create_platform_invitation(): void
    {
        $admin = $this->createPlatformUser(['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);
        $role = Role::query()->create([
            'scope' => 'platform',
            'name' => 'Platform Operator',
        ]);
        $this->withToken($token)
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'platform',
                'full_name' => 'Platform Invitee',
                'email' => 'platform-invited@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.scope', 'platform')
            ->assertJsonPath('data.school_id', null);

        $invitee = User::query()
            ->whereNull('school_id')
            ->where('email', 'platform-invited@example.test')
            ->firstOrFail();

        $this->assertSame('invited', $invitee->status);
        $this->assertTrue($invitee->roles()->whereKey($role->id)->exists());
    }

    public function test_invalid_platform_roles_do_not_provision_an_invitee(): void
    {
        $admin = $this->createPlatformUser(['account_lifecycle.manage']);
        $inactiveRole = Role::query()->create([
            'scope' => 'platform',
            'name' => 'Inactive Platform Operator',
            'status' => 'inactive',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'platform',
                'full_name' => 'Invalid Platform Invitee',
                'email' => 'invalid-platform-invited@example.test',
                'role_ids' => [$inactiveRole->uuid],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-platform-invited@example.test',
        ]);
    }

    public function test_invitation_does_not_create_a_user_from_draft_data(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Invited Teacher',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'school',
                'school_id' => $school->uuid,
                'full_name' => 'Unsaved Draft',
                'email' => 'unsaved@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertConflict();

        $this->assertDatabaseMissing('users', ['email' => 'unsaved@example.test']);
    }
}
