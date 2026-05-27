<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AuthToken;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserDeactivationBearerTokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_deactivation_revokes_all_active_bearer_tokens_for_target_user(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.view', 'users.lifecycle']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);
        $targetToken = $this->bearerTokenFor($target);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/deactivate", [
                'effective_at' => '2026-05-27',
                'reason' => 'Access no longer required',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertSame(
            0,
            AuthToken::query()->where('user_id', $target->id)->whereNull('revoked_at')->count(),
        );

        $this->withToken($targetToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_revoked');
    }
}
