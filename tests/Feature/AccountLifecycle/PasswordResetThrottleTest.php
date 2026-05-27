<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PasswordResetThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_throttle_preserves_accepted_response_without_token_creation(): void
    {
        $school = School::factory()->create();
        User::factory()->create([
            'school_id' => $school->id,
            'email' => 'throttle@example.test',
            'status' => 'active',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/password-reset-requests', [
                'email' => 'throttle@example.test',
                'school_id' => $school->uuid,
            ])->assertAccepted();
        }

        $this->postJson('/api/v1/auth/password-reset-requests', [
            'email' => 'throttle@example.test',
            'school_id' => $school->uuid,
        ])->assertAccepted();

        $this->assertSame(3, PasswordResetRequest::query()->whereNotNull('token_hash')->count());
    }

    public function test_failed_reset_completion_suppresses_new_tokens_without_locking_account(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'suppressed@example.test',
            'status' => 'active',
        ]);
        $plainResetToken = 'expired-reset-token-with-enough-length-456';

        PasswordResetRequest::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', 'suppressed@example.test|'.$school->id),
            'token_hash' => hash('sha256', $plainResetToken),
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/password-resets', [
                'token' => $plainResetToken,
                'password' => 'another-secure-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/password-reset-requests', [
            'email' => 'suppressed@example.test',
            'school_id' => $school->uuid,
        ])->assertAccepted();

        $this->assertSame(1, PasswordResetRequest::query()->whereNotNull('token_hash')->count());
    }

    public function test_unknown_reset_token_attempts_suppress_new_reset_tokens_by_ip(): void
    {
        $school = School::factory()->create();
        User::factory()->create([
            'school_id' => $school->id,
            'email' => 'ip-suppressed@example.test',
            'status' => 'active',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
                ->postJson('/api/v1/auth/password-resets', [
                    'token' => 'random-invalid-reset-token-1234567890',
                    'password' => 'another-secure-password',
                ])
                ->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson('/api/v1/auth/password-reset-requests', [
                'email' => 'ip-suppressed@example.test',
                'school_id' => $school->uuid,
            ])
            ->assertAccepted();

        $this->assertSame(0, PasswordResetRequest::query()->whereNotNull('token_hash')->count());
    }
}
