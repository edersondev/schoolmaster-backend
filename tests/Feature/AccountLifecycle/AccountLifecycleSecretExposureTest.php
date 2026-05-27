<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Models\AccountInvitation;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use App\Services\AccountLifecycle\LifecycleTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountLifecycleSecretExposureTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_setup_response_and_audit_do_not_expose_lifecycle_secrets(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create([
            'school_id' => $school->id,
            'status' => 'invited',
        ]);
        $plainToken = 'known-invitation-token-with-enough-length-123';
        $plainPassword = 'new-secure-password-123';
        $tokenHash = app(LifecycleTokenService::class)->hash($plainToken);

        AccountInvitation::query()->create([
            'target_user_id' => $user->id,
            'school_id' => $school->id,
            'scope' => 'school',
            'token_hash' => $tokenHash,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'send_count' => 1,
            'send_window_started_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/account-invitations/{$plainToken}/setup", [
            'password' => $plainPassword,
        ])->assertOk();

        $responseBody = $response->getContent();
        $this->assertStringNotContainsString($plainToken, $responseBody);
        $this->assertStringNotContainsString($plainPassword, $responseBody);
        $this->assertStringNotContainsString($tokenHash, $responseBody);
        $this->assertArrayNotHasKey('token', $response->json('data'));
        $this->assertArrayNotHasKey('token_hash', $response->json('data'));
        $this->assertArrayNotHasKey('password', $response->json('data'));

        $auditPayload = AuditEvent::query()
            ->where('event_type', 'account_invitation_completed')
            ->firstOrFail()
            ->tenant_safe_metadata;

        $this->assertStringNotContainsString($plainToken, json_encode($auditPayload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($plainPassword, json_encode($auditPayload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($tokenHash, json_encode($auditPayload, JSON_THROW_ON_ERROR));
    }
}
