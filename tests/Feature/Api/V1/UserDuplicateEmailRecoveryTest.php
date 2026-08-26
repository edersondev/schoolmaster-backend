<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class UserDuplicateEmailRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_same_school_deleted_owner_returns_minimal_recovery_conflict_and_safe_audit(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $owner = User::factory()->create(['school_id' => $school->id, 'email' => 'joao@teste.com.br']);
        $owner->delete();

        $response = $this->createUser($actor, $school, $role, '  JOAO@TESTE.COM.BR  ')->assertConflict();

        $this->assertSame([
            'error' => [
                'code' => 'recoverable_user_conflict',
                'message' => 'A retained user can be restored.',
                'details' => ['user_id' => $owner->uuid, 'recommended_action' => 'restore'],
            ],
        ], $response->json());
        $this->assertSame(1, User::withTrashed()->where('identity_email_key', 'joao@teste.com.br')->count());
        $this->assertDatabaseMissing('role_user', ['user_id' => $owner->id, 'role_id' => $role->id]);

        $audit = AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->sole();
        $this->assertSame($actor->id, $audit->actor_user_id);
        $this->assertSame($school->id, $audit->school_id);
        $this->assertSame('recoverable_user_conflict', $audit->outcome);
        $this->assertSame('user', $audit->affected_resource_type);
        $this->assertSame($owner->uuid, $audit->affected_resource_id);
        $this->assertSame('127.0.0.1', $audit->source_ip);
        $this->assertSame([
            'scope' => 'school',
            'workflow' => 'direct_user_creation',
            'email_hash' => hash('sha256', 'joao@teste.com.br'),
            'reason_code' => 'recoverable_user_conflict',
        ], $audit->tenant_safe_metadata);
        $this->assertStringNotContainsString('joao@teste.com.br', $audit->toJson());
    }

    public function test_active_and_hidden_deleted_owners_return_identical_generic_validation_without_target(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $activeOwner = User::factory()->create(['school_id' => $school->id, 'email' => 'active@example.test']);
        $hiddenOwner = User::factory()->create(['school_id' => $otherSchool->id, 'email' => 'hidden@example.test']);
        $hiddenOwner->delete();

        $activeResponse = $this->createUser($actor, $school, $role, $activeOwner->email)->assertUnprocessable();
        $hiddenResponse = $this->createUser($actor, $school, $role, ' HIDDEN@EXAMPLE.TEST ')->assertUnprocessable();

        $expected = [
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Validation failed.',
                'details' => ['fields' => ['email' => ['The email is unavailable.']]],
            ],
        ];
        $this->assertSame($expected, $activeResponse->json());
        $this->assertSame($activeResponse->json(), $hiddenResponse->json());
        $this->assertSame(2, AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->count());

        foreach (AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->get() as $audit) {
            $this->assertSame('validation_failed', $audit->outcome);
            $this->assertNull($audit->affected_resource_type);
            $this->assertNull($audit->affected_resource_id);
            $this->assertSame('email_unavailable', $audit->tenant_safe_metadata['reason_code']);
        }
    }

    public function test_recovery_conflict_is_followed_by_explicit_restore_and_existing_update_workflow(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $newRole = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Recovered User Role',
        ]);
        $owner = User::factory()->create(['school_id' => $school->id, 'email' => 'recover@example.test']);
        $owner->delete();

        $conflict = $this->createUser($actor, $school, $role, $owner->email)->assertConflict();
        $this->assertSame($owner->uuid, $conflict->json('error.details.user_id'));
        $this->assertSoftDeleted('users', ['id' => $owner->id]);

        $token = $this->bearerTokenFor($actor);
        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$owner->uuid}/restore", [
                'effective_at' => now()->toDateString(),
                'reason' => 'Approved retained identity recovery',
            ])
            ->assertOk();
        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/users/{$owner->uuid}", [
                'full_name' => 'Joao Recovered',
                'role_ids' => [$newRole->uuid],
            ])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Joao Recovered');

        $restored = User::query()->where('uuid', $owner->uuid)->sole();
        $this->assertSame($owner->id, $restored->id);
        $this->assertSame('recover@example.test', $restored->email);
        $this->assertTrue($restored->roles()->whereKey($newRole->id)->exists());
    }

    public function test_recovery_guidance_does_not_bypass_a_later_restore_blocker(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $owner = User::factory()->create(['school_id' => $school->id, 'email' => 'blocked-restore@example.test']);
        $owner->delete();

        $this->createUser($actor, $school, $role, $owner->email)->assertConflict();
        $school->forceFill(['status' => School::STATUS_INACTIVE])->save();

        $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$owner->uuid}/restore", [
                'effective_at' => now()->toDateString(),
                'reason' => 'Attempted recovery after scope became inactive',
            ])
            ->assertUnauthorized();

        $this->assertSoftDeleted('users', ['id' => $owner->id]);
        $this->assertSame(1, User::withTrashed()->where('identity_email_key', $owner->email)->count());
    }

    public function test_new_email_is_trimmed_lowercased_and_stored_once(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();

        $this->createUser($actor, $school, $role, '  New.User@Example.TEST  ')->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'new.user@example.test']);
        $this->assertSame(1, User::withTrashed()->where('identity_email_key', 'new.user@example.test')->count());
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_every_non_recoverable_owner_state_is_privately_indistinguishable(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $inactiveSchool = School::factory()->inactive()->create();
        $authorized = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $missingView = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();

        foreach (['active', 'inactive', 'invited'] as $status) {
            User::factory()->create([
                'school_id' => $school->id,
                'email' => "same-{$status}@example.test",
                'status' => $status,
            ]);
        }

        $unauthorizedDeleted = User::factory()->create(['school_id' => $school->id, 'email' => 'missing-view@example.test']);
        $crossTenant = User::factory()->create(['school_id' => $otherSchool->id, 'email' => 'cross-tenant@example.test']);
        $inactiveParent = User::factory()->create(['school_id' => $inactiveSchool->id, 'email' => 'inactive-parent@example.test']);
        $platformOwner = User::factory()->create(['school_id' => null, 'email' => 'platform-owner@example.test']);
        $unauthorizedDeleted->delete();
        $crossTenant->delete();
        $inactiveParent->delete();
        $platformOwner->delete();
        User::factory()->create(['email' => 'ambiguous@example.test']);
        User::factory()->create(['email' => ' ambiguous@example.test ']);

        $cases = [
            [$authorized, 'same-active@example.test'],
            [$authorized, 'same-inactive@example.test'],
            [$authorized, 'same-invited@example.test'],
            [$missingView, 'missing-view@example.test'],
            [$authorized, 'cross-tenant@example.test'],
            [$authorized, 'inactive-parent@example.test'],
            [$authorized, 'platform-owner@example.test'],
            [$authorized, 'ambiguous@example.test'],
        ];
        $expectedResponse = null;

        foreach ($cases as [$actor, $email]) {
            $ownerCount = User::withTrashed()->where('identity_email_key', $email)->count();
            $response = $this->createUser($actor, $school, $role, strtoupper($email))->assertUnprocessable();
            $expectedResponse ??= $response->json();
            $this->assertSame($expectedResponse, $response->json());
            $this->assertSame($ownerCount, User::withTrashed()->where('identity_email_key', $email)->count());
        }

        $audits = AuditEvent::query()->where('event_type', 'user_creation_duplicate_email')->get();
        $this->assertCount(count($cases), $audits);
        foreach ($audits as $audit) {
            $this->assertSame('validation_failed', $audit->outcome);
            $this->assertNull($audit->affected_resource_id);
            $this->assertSame('email_unavailable', $audit->tenant_safe_metadata['reason_code']);
            $this->assertSame(['scope', 'workflow', 'email_hash', 'reason_code'], array_keys($audit->tenant_safe_metadata));
        }
    }

    public function test_direct_creation_enforces_the_published_255_character_email_limit(): void
    {
        $school = School::factory()->create();
        $actor = $this->createSchoolAdmin($school, ['users.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();

        $this->createUser($actor, $school, $role, $this->emailOfLength(256))
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    private function createUser(User $actor, School $school, Role $role, string $email): TestResponse
    {
        return $this->withToken($this->bearerTokenFor($actor))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'New User',
                'email' => $email,
                'role_ids' => [$role->uuid],
            ]);
    }

    private function emailOfLength(int $length): string
    {
        $domain = str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 58).'.'.str_repeat('e', $length - 252);

        return str_repeat('a', 64).'@'.$domain;
    }
}
