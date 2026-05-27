<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AccountLifecycleResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_and_accepted_envelopes_are_consistent(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertOk()
            ->assertJsonStructure(['data' => ['user_id', 'school_id', 'status'], 'meta']);

        $this->postJson('/api/v1/auth/password-reset-requests', [
            'email' => 'missing-user@example.test',
        ])
            ->assertAccepted()
            ->assertJsonStructure(['data' => ['accepted'], 'meta']);
    }

    public function test_standard_error_envelopes_are_consistent(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);
        $platformAdmin = $this->createPlatformUser(['account_lifecycle.manage']);

        $this->postJson('/api/v1/auth/password-reset-requests', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['error' => ['code', 'message', 'details']])
            ->assertJsonPath('error.code', 'validation_failed');

        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);

        $this->withToken($this->bearerTokenFor($platformAdmin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-lock", [
                'reason' => 'Not permitted',
            ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-lock", [
                'reason' => 'Wrong tenant',
            ])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'reactivate',
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'conflict');

        $this->postJson('/api/v1/account-invitations/not-a-real-token/setup', [
            'password' => 'new-secure-password-123',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'token_invalid');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users/'.Str::uuid().'/account-lock')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }
}
