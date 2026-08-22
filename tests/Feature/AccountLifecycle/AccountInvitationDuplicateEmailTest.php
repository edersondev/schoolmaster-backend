<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\AccountInvitationMail;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AccountInvitationDuplicateEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
    }

    public function test_deleted_platform_owner_returns_generic_validation_and_leaves_no_partial_state(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $role = Role::query()->create(['scope' => 'platform', 'name' => 'Platform Operator']);
        $owner = User::factory()->create([
            'school_id' => null,
            'email' => 'platform-owner@example.test',
            'status' => 'invited',
        ]);
        $owner->delete();

        $response = $this->withToken($this->bearerTokenFor($actor))
            ->postJson('/api/v1/account-invitations', $this->payload($role, ' PLATFORM-OWNER@EXAMPLE.TEST '))
            ->assertUnprocessable();

        $this->assertSame([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Validation failed.',
                'details' => ['fields' => ['email' => ['The email is unavailable.']]],
            ],
        ], $response->json());
        $this->assertSame(1, User::withTrashed()->where('identity_email_key', 'platform-owner@example.test')->count());
        $this->assertDatabaseCount('account_invitations', 0);
        $this->assertDatabaseMissing('role_user', ['user_id' => $owner->id, 'role_id' => $role->id]);
        Mail::assertNothingSent();

        $audit = AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->sole();
        $this->assertSame($actor->id, $audit->actor_user_id);
        $this->assertNull($audit->school_id);
        $this->assertSame('validation_failed', $audit->outcome);
        $this->assertNull($audit->affected_resource_type);
        $this->assertNull($audit->affected_resource_id);
        $this->assertSame([
            'scope' => 'platform',
            'workflow' => 'account_invitation',
            'email_hash' => hash('sha256', 'platform-owner@example.test'),
            'reason_code' => 'email_unavailable',
        ], $audit->tenant_safe_metadata);
        $this->assertStringNotContainsString('platform-owner@example.test', $audit->toJson());
    }

    public function test_active_and_deleted_platform_owners_have_identical_generic_responses(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $role = Role::query()->create(['scope' => 'platform', 'name' => 'Platform Operator']);
        User::factory()->create(['school_id' => null, 'email' => 'active-owner@example.test', 'status' => 'active']);
        $deleted = User::factory()->create(['school_id' => null, 'email' => 'deleted-owner@example.test']);
        $deleted->delete();

        $active = $this->withToken($this->bearerTokenFor($actor))
            ->postJson('/api/v1/account-invitations', $this->payload($role, 'active-owner@example.test'))
            ->assertUnprocessable();
        $deletedResponse = $this->withToken($this->bearerTokenFor($actor))
            ->postJson('/api/v1/account-invitations', $this->payload($role, 'deleted-owner@example.test'))
            ->assertUnprocessable();

        $this->assertSame($active->json(), $deletedResponse->json());
        $this->assertDatabaseCount('account_invitations', 0);
        $this->assertSame(2, AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->count());
        Mail::assertNothingSent();
    }

    public function test_new_platform_invitation_canonicalizes_email_and_sends_once(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $role = Role::query()->create(['scope' => 'platform', 'name' => 'Platform Operator']);

        $this->withToken($this->bearerTokenFor($actor))
            ->postJson('/api/v1/account-invitations', $this->payload($role, ' NEW-INVITEE@EXAMPLE.TEST '))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'new-invitee@example.test', 'status' => 'invited']);
        $this->assertDatabaseCount('account_invitations', 1);
        $this->assertDatabaseCount('audit_events', 1);
        Mail::assertSentCount(1);
        Mail::assertSent(AccountInvitationMail::class);
    }

    public function test_platform_invitation_enforces_the_published_255_character_email_limit(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $role = Role::query()->create(['scope' => 'platform', 'name' => 'Platform Operator']);

        $this->withToken($this->bearerTokenFor($actor))
            ->postJson('/api/v1/account-invitations', $this->payload($role, $this->emailOfLength(256)))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    private function payload(Role $role, string $email): array
    {
        return [
            'scope' => 'platform',
            'full_name' => 'Platform Invitee',
            'email' => $email,
            'role_ids' => [$role->uuid],
        ];
    }

    private function emailOfLength(int $length): string
    {
        $domain = str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 58).'.'.str_repeat('e', $length - 252);

        return str_repeat('a', 64).'@'.$domain;
    }
}
