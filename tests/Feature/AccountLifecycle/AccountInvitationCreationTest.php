<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\AccountInvitationMail;
use App\Models\AccountInvitation;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class AccountInvitationCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
    }

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
        $invitee = User::factory()->create([
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
            ->assertJsonPath('data.delivery_channel', 'email')
            ->assertJsonMissingPath('data.token')
            ->assertJsonMissingPath('data.token_hash');

        Mail::assertSent(AccountInvitationMail::class, function (AccountInvitationMail $mail) use ($invitee): bool {
            return $mail->hasTo($invitee->email)
                && parse_url($mail->setupUrl, PHP_URL_PATH) === '/auth/account-invitations/setup'
                && str_starts_with((string) parse_url($mail->setupUrl, PHP_URL_FRAGMENT), 'token=');
        });

        $invitation = AccountInvitation::query()->sole();
        $this->assertNotNull($invitation->delivery_requested_at);
        $this->assertSame('email', $invitation->delivery_channel);
        $this->assertStringNotContainsString(
            $invitation->token_hash,
            json_encode($invitation->email_delivery_metadata_summary, JSON_THROW_ON_ERROR),
        );
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

    public function test_emailed_token_completes_setup_once_and_allows_login(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Invited Teacher',
        ]);
        $invitee = User::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Emailed Invitee',
            'email' => 'emailed-invitee@example.test',
            'status' => 'invited',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', [
                'scope' => 'school',
                'school_id' => $school->uuid,
                'full_name' => $invitee->full_name,
                'email' => $invitee->email,
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated();

        /** @var AccountInvitationMail $mail */
        $mail = Mail::sent(AccountInvitationMail::class)->sole();
        $path = (string) parse_url($mail->setupUrl, PHP_URL_PATH);
        parse_str((string) parse_url($mail->setupUrl, PHP_URL_FRAGMENT), $fragment);
        $plainToken = $fragment['token'] ?? '';

        $this->assertSame('/auth/account-invitations/setup', $path);
        $this->assertNotSame('', $plainToken);
        $this->assertSame(hash('sha256', $plainToken), AccountInvitation::query()->sole()->token_hash);

        $this->postJson('/api/v1/account-invitations/setup', [
            'invitation_token' => $plainToken,
            'password' => 'correct-horse-battery-staple',
        ])->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/api/v1/account-invitations/setup', [
            'invitation_token' => $plainToken,
            'password' => 'another-correct-horse-battery-staple',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_invalid');

        $this->postJson('/api/v1/auth/login', [
            'email' => $invitee->email,
            'password' => 'correct-horse-battery-staple',
            'school_id' => $school->uuid,
        ])->assertOk();
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
        Mail::assertNothingSent();
    }
}
