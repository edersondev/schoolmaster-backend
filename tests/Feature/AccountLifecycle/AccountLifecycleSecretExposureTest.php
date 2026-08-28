<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\PasswordDeliveryMail;
use App\Models\AccountInvitation;
use App\Models\AuditEvent;
use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use App\Services\AccountLifecycle\LifecycleTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

        $response = $this->postJson('/api/v1/account-invitations/setup', [
            'invitation_token' => $plainToken,
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

    public function test_password_delivery_response_persistence_and_audit_expose_no_reusable_secret(): void
    {
        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $target = User::factory()->create([
            'school_id' => $school->id,
            'email' => 'private-delivery-target@example.test',
            'status' => 'active',
        ]);

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertCreated();

        /** @var PasswordDeliveryMail $mail */
        $mail = Mail::sent(PasswordDeliveryMail::class)->sole();
        parse_str((string) parse_url($mail->passwordUrl, PHP_URL_FRAGMENT), $fragment);
        $plainToken = $fragment['token'] ?? '';
        $tokenHash = hash('sha256', $plainToken);
        $responseBody = $response->getContent();
        $reset = PasswordResetRequest::query()->sole();
        $audit = AuditEvent::query()->where('event_type', 'password_delivery_requested')->sole();
        $persistedSafeMetadata = json_encode($reset->email_delivery_metadata_summary, JSON_THROW_ON_ERROR);
        $auditSafeMetadata = json_encode($audit->tenant_safe_metadata, JSON_THROW_ON_ERROR);

        foreach ([$plainToken, $tokenHash, $target->email, $mail->passwordUrl] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $responseBody);
            $this->assertStringNotContainsString($forbidden, $persistedSafeMetadata);
            $this->assertStringNotContainsString($forbidden, $auditSafeMetadata);
        }

        $this->assertSame(['status', 'delivery_channel', 'delivery_requested_at'], array_keys($response->json('data')));
    }
}
