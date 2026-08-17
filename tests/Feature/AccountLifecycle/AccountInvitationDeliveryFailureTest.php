<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\AccountInvitationMail;
use App\Models\AccountInvitation;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

final class AccountInvitationDeliveryFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_failure_returns_safe_503_with_undelivered_invitation(): void
    {
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
        [$school, $admin, $role, $invitee] = $this->invitationActors();
        $payload = $this->payload($school, $role, $invitee);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('smtp-provider-secret'));

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', $payload);

        $response->assertStatus(503)
            ->assertJsonPath('error.code', 'temporary_unavailable')
            ->assertJsonMissingPath('error.details.provider')
            ->assertDontSee('smtp-provider-secret');

        $failed = AccountInvitation::query()->sole();
        $this->assertNull($failed->delivery_requested_at);
        $this->assertNull($failed->delivery_channel);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'account_invitation_delivery_failed',
            'outcome' => 'failure',
            'affected_resource_id' => $invitee->uuid,
        ]);

    }

    public function test_retry_replaces_an_undelivered_pending_invitation(): void
    {
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
        [$school, $admin, $role, $invitee] = $this->invitationActors();
        $failed = AccountInvitation::query()->create([
            'target_user_id' => $invitee->id,
            'school_id' => $school->id,
            'actor_user_id' => $admin->id,
            'scope' => 'school',
            'token_hash' => hash('sha256', 'unknown-undelivered-token'),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'send_count' => 1,
            'send_window_started_at' => now(),
        ]);
        Mail::fake();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', $this->payload($school, $role, $invitee))
            ->assertCreated();

        $this->assertSame('superseded', $failed->refresh()->status);
        $this->assertSame(1, AccountInvitation::query()->where('status', 'pending')->count());
        Mail::assertSent(AccountInvitationMail::class, $invitee->email);
    }

    public function test_unsafe_frontend_origin_returns_503_without_sending_mail(): void
    {
        config(['app.frontend_url' => 'javascript:alert(1)']);
        [$school, $admin, $role, $invitee] = $this->invitationActors();
        Mail::fake();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/account-invitations', $this->payload($school, $role, $invitee))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'temporary_unavailable');

        Mail::assertNothingSent();
    }

    /**
     * @return array{School, User, Role, User}
     */
    private function invitationActors(): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Invited Teacher',
        ]);
        $invitee = User::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Invited User',
            'email' => 'delivery-invitee@example.test',
            'status' => 'invited',
        ]);

        return [$school, $admin, $role, $invitee];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(School $school, Role $role, User $invitee): array
    {
        return [
            'scope' => 'school',
            'school_id' => $school->uuid,
            'full_name' => $invitee->full_name,
            'email' => $invitee->email,
            'role_ids' => [$role->uuid],
        ];
    }
}
