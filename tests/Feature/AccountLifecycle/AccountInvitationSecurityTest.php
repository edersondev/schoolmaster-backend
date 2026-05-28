<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountInvitation;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountInvitationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_limits_and_token_supersession_are_enforced(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'School User',
        ]);
        $payload = [
            'scope' => 'school',
            'school_id' => $school->uuid,
            'full_name' => 'Limited Invitee',
            'email' => 'limited@example.test',
            'role_ids' => [$role->uuid],
        ];

        for ($i = 0; $i < 3; $i++) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson('/api/v1/account-invitations', $payload)
                ->assertCreated();
        }

        $this->assertSame(2, AccountInvitation::query()->where('status', 'superseded')->count());

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', $payload)
            ->assertConflict();
    }

    public function test_failed_completion_attempts_revoke_invitation(): void
    {
        $school = School::factory()->create();
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'School User',
        ]);
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $token = $this->bearerTokenFor($admin);

        $response = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'school',
                'school_id' => $school->uuid,
                'full_name' => 'Failure Invitee',
                'email' => 'failure@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated()
            ->json('data');

        $invitation = AccountInvitation::query()->where('uuid', $response['id'])->firstOrFail();
        $plainToken = 'known-invitation-token-with-enough-length-456';
        $invitation->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->subMinute(),
        ])->save();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/v1/account-invitations/{$plainToken}/setup", [
                'password' => 'valid-password-for-failure-attempts',
            ])->assertUnauthorized();
        }

        $this->assertSame('revoked', $invitation->refresh()->status);
    }

    public function test_invitation_rejects_inactive_and_deleted_existing_users(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'School User',
        ]);

        $inactiveUser = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'inactive-invite@example.test',
            'status' => 'inactive',
        ]);
        $deletedUser = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'deleted-invite@example.test',
            'status' => 'invited',
        ]);
        $deletedUser->delete();

        foreach ([$inactiveUser->email, $deletedUser->email] as $email) {
            $this->withToken($this->bearerTokenFor($admin))
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson('/api/v1/account-invitations', [
                    'scope' => 'school',
                    'school_id' => $school->uuid,
                    'full_name' => 'Blocked Invitee',
                    'email' => $email,
                    'role_ids' => [$role->uuid],
                ])
                ->assertConflict();
        }
    }

    public function test_unknown_invitation_token_attempts_are_rate_limited_by_ip(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'token-limit@example.test',
            'status' => 'invited',
        ]);
        $invitation = AccountInvitation::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'scope' => 'school',
            'token_hash' => hash('sha256', 'known-valid-invitation-token-1234567890'),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->postJson('/api/v1/account-invitations/random-invalid-token/setup', [
                    'password' => 'valid-password-for-setup',
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/account-invitations/known-valid-invitation-token-1234567890/setup', [
                'password' => 'valid-password-for-setup',
            ])
            ->assertUnauthorized();

        $this->assertSame('pending', $invitation->refresh()->status);
    }
}
