<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
}
