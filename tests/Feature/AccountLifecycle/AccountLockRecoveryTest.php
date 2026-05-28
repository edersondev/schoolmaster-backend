<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountLock;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountLockRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_lock_and_unlock_account_with_token_revocation_and_audit(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);
        $targetToken = $this->bearerTokenFor($target);
        $adminToken = $this->bearerTokenFor($admin);

        $this->withToken($adminToken)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-lock", [
                'reason' => 'Security review',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withToken($targetToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->assertDatabaseHas('audit_events', ['event_type' => 'account_locked']);

        $this->withToken($adminToken)
            ->withHeader('X-School-Id', $school->uuid)
            ->deleteJson("/api/v1/users/{$target->uuid}/account-lock")
            ->assertOk()
            ->assertJsonPath('data.action', 'account_unlocked');

        $this->assertSame('cleared', AccountLock::query()->firstOrFail()->status);
        $this->assertGreaterThanOrEqual(2, AuditEvent::query()->whereIn('event_type', ['account_locked', 'account_unlocked'])->count());
    }

    public function test_recovery_unlock_clears_administrative_lock(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ]);

        AccountLock::query()->create([
            'user_id' => $target->id,
            'school_id' => $school->id,
            'actor_user_id' => $admin->id,
            'lock_type' => 'administrative',
            'status' => 'active',
            'reason' => 'Security review',
            'locked_at' => now(),
        ]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/account-reactivation", [
                'action' => 'unlock',
                'reason' => 'Support verified access',
            ])
            ->assertOk()
            ->assertJsonPath('data.action', 'account_unlocked');

        $this->assertDatabaseHas('account_locks', [
            'user_id' => $target->id,
            'status' => 'cleared',
        ]);
        $this->assertDatabaseHas('account_recoveries', [
            'user_id' => $target->id,
            'recovery_type' => 'unlock',
        ]);
    }
}
