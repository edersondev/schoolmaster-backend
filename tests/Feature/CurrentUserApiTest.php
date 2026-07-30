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

    public function test_system_administrator_role_is_exposed_without_a_response_schema_change(): void
    {
        $user = $this->createSystemAdministrator();

        $this->withToken($this->bearerTokenFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonCount(1, 'data.roles')
            ->assertJsonPath('data.roles.0.name', 'System Administrator')
            ->assertJsonCount(0, 'data.permissions')
            ->assertJsonMissingPath('data.master_access');
    }

    public function test_system_administrator_without_school_header_has_null_resolved_school(): void
    {
        $user = $this->createSystemAdministrator();

        $this->withToken($this->bearerTokenFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.resolved_school', null);
    }

    public function test_system_administrator_can_resolve_exact_active_school_from_header(): void
    {
        $school = School::factory()->create();
        $user = $this->createSystemAdministrator();

        $this->withToken($this->bearerTokenFor($user))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.resolved_school.id', $school->uuid)
            ->assertJsonPath('data.resolved_school.status', School::STATUS_ACTIVE);
    }

    public function test_limited_platform_user_cannot_expose_school_selected_by_header(): void
    {
        $school = School::factory()->create();
        $user = $this->createLimitedPlatformUser();

        $this->withToken($this->bearerTokenFor($user))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.resolved_school', null);
    }

    public function test_authorized_platform_user_can_resolve_school_selected_by_header(): void
    {
        $school = School::factory()->create();
        $user = $this->createPlatformUser(['schools.view']);

        $this->withToken($this->bearerTokenFor($user))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.resolved_school.id', $school->uuid);
    }

    public function test_system_administrator_cannot_resolve_inactive_or_unknown_school(): void
    {
        $inactiveSchool = School::factory()->inactive()->create();
        $user = $this->createSystemAdministrator();
        $token = $this->bearerTokenFor($user);

        $this->withToken($token)
            ->withHeader('X-School-Id', $inactiveSchool->uuid)
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($token)
            ->withHeader('X-School-Id', fake()->uuid())
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
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
