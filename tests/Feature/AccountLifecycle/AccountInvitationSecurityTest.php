<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountInvitation;
use App\Models\Role;
use App\Models\School;
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
}
