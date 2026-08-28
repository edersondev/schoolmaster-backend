<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\PasswordDeliveryMail;
use App\Models\AccountLock;
use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class PasswordResetCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_completion_changes_password_and_revokes_active_tokens(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'complete-reset@example.test',
            'password' => Hash::make('old-password-value'),
            'status' => 'active',
        ]);
        $bearer = $this->bearerTokenFor($user);
        $plainResetToken = 'password-reset-token-with-enough-length-123';

        PasswordResetRequest::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', 'complete-reset@example.test|'.$school->id),
            'token_hash' => hash('sha256', $plainResetToken),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $plainResetToken,
            'password' => 'new-secure-password-value',
        ])->assertOk()
            ->assertJsonPath('data.action', 'password_reset_completed');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'complete-reset@example.test',
            'password' => 'new-secure-password-value',
            'school_id' => $school->uuid,
        ])->assertOk();

        $this->withToken($bearer)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_administrator_delivered_token_completes_once_and_revokes_existing_sessions(): void
    {
        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $existingSession = $this->bearerTokenFor($user);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/password-delivery")
            ->assertCreated();

        /** @var PasswordDeliveryMail $mail */
        $mail = Mail::sent(PasswordDeliveryMail::class)->sole();
        parse_str((string) parse_url($mail->passwordUrl, PHP_URL_FRAGMENT), $fragment);
        $plainToken = $fragment['token'] ?? '';

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $plainToken,
            'password' => 'delivered-secure-password-value',
        ])->assertOk()
            ->assertJsonPath('data.action', 'password_reset_completed');

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $plainToken,
            'password' => 'second-secure-password-value',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_invalid');

        $this->withToken($existingSession)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_delivery_tokens_preserve_neutral_invalid_states_and_locked_conflict(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);

        foreach ([
            ['token' => 'expired-delivery-token-with-safe-length-123', 'status' => 'pending', 'expires_at' => now()->subMinute()],
            ['token' => 'reused-delivery-token-with-safe-length-12345', 'status' => 'completed', 'expires_at' => now()->addMinutes(30)],
            ['token' => 'superseded-delivery-token-safe-length-123', 'status' => 'superseded', 'expires_at' => now()->addMinutes(30)],
        ] as $state) {
            PasswordResetRequest::query()->create([
                'target_user_id' => $user->id,
                'school_id' => $school->id,
                'account_identifier_hash' => hash('sha256', $user->email.'|'.$school->id),
                'token_hash' => hash('sha256', $state['token']),
                'status' => $state['status'],
                'expires_at' => $state['expires_at'],
            ]);

            $this->withServerVariables(['REMOTE_ADDR' => fake()->ipv4()])
                ->postJson('/api/v1/auth/password-resets', [
                    'token' => $state['token'],
                    'password' => 'valid-secure-password-value',
                ])->assertUnauthorized()
                ->assertJsonPath('error.code', 'token_invalid');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.80'])
            ->postJson('/api/v1/auth/password-resets', [
                'token' => 'unknown-malformed-but-safe-length-token-value',
                'password' => 'valid-secure-password-value',
            ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_invalid');

        $lockedToken = 'locked-delivery-token-with-safe-length-12345';
        PasswordResetRequest::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', $user->email.'|'.$school->id),
            'token_hash' => hash('sha256', $lockedToken),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);
        AccountLock::query()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'lock_type' => 'administrative',
            'status' => 'active',
            'locked_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $lockedToken,
            'password' => 'valid-secure-password-value',
        ])->assertConflict();
    }

    public function test_password_validation_retry_keeps_delivery_token_pending_until_success(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $plainToken = 'validation-retry-delivery-token-safe-length';
        $reset = PasswordResetRequest::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', $user->email.'|'.$school->id),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $plainToken,
            'password' => 'short',
        ])->assertUnprocessable();

        $this->assertSame('pending', $reset->refresh()->status);

        $this->postJson('/api/v1/auth/password-resets', [
            'token' => $plainToken,
            'password' => 'valid-secure-password-after-retry',
        ])->assertOk();
    }

    public function test_five_failed_completions_block_new_administrator_delivery_for_the_account(): void
    {
        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $user = User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $plainToken = 'expired-account-suppression-token-safe-length';

        PasswordResetRequest::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', $user->email.'|'.$school->id),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.90'])
                ->postJson('/api/v1/auth/password-resets', [
                    'token' => $plainToken,
                    'password' => 'valid-secure-password-value',
                ])->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.91'])
            ->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$user->uuid}/password-delivery")
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'password_delivery_rate_limited');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_requests', 1);
    }
}
