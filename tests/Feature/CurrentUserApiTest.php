<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CurrentUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_current_user_roles_permissions_and_resolved_school(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'password' => Hash::make('password'),
        ]);
        $token = $this->bearerTokenFor($user);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->uuid)
            ->assertJsonPath('data.resolved_school.id', $school->uuid)
            ->assertJsonStructure(['data' => ['roles', 'permissions']]);
    }

    public function test_rejects_unauthorized_token(): void
    {
        $this->withToken('invalid')->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_revoked');
    }

    public function test_rejects_tenant_mismatch_context(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'password' => Hash::make('password'),
        ]);

        $this->withToken($this->bearerTokenFor($user))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
