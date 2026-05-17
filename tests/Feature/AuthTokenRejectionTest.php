<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthTokenRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_missing_and_revoked_bearer_tokens_with_error_envelope(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthorized');

        $user = $this->createPlatformUser();
        $plainToken = $this->bearerTokenFor($user);
        AuthToken::query()->firstOrFail()->forceFill(['revoked_at' => now()])->save();

        $this->withToken($plainToken)->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_revoked');
    }
}
