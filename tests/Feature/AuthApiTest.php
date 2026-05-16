<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuthToken;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_logout_revocation_and_expiry_behaviors(): void
    {
        $user = $this->createPlatformUser();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure(['data' => ['token', 'token_expires_at'], 'meta']);

        $token = $login->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_revoked');

        $expiredToken = $this->bearerTokenFor($user);
        AuthToken::query()->whereNull('revoked_at')->latest('id')->firstOrFail()
            ->forceFill(['expires_at' => now()->subMinute()])
            ->save();

        $this->withToken($expiredToken)->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_expired');
    }

    public function test_failed_login_lockout_inactive_user_and_inactive_school_are_rejected(): void
    {
        $school = School::factory()->inactive()->create();
        $inactiveSchoolUser = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'inactive-school@example.com',
            'password' => Hash::make('password'),
        ]);
        $inactiveUser = User::factory()->create([
            'email' => 'inactive-user@example.com',
            'password' => Hash::make('password'),
            'status' => 'inactive',
        ]);

        $this->postJson('/api/v1/auth/login', ['email' => $inactiveUser->email, 'password' => 'password'])
            ->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', ['email' => $inactiveSchoolUser->email, 'password' => 'password'])
            ->assertUnauthorized();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'missing@example.com', 'password' => 'bad']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'missing@example.com', 'password' => 'bad'])
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'auth_locked');
    }
}
