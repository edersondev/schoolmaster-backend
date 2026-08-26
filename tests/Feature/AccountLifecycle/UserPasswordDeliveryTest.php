<?php

declare(strict_types=1);

namespace Tests\Feature\AccountLifecycle;

use App\Mail\PasswordDeliveryMail;
use App\Models\AccountLock;
use App\Models\LoginAttempt;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

final class UserPasswordDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();
        config(['app.frontend_url' => 'https://app.schoolmaster.test']);
    }

    public function test_active_creation_sends_no_mail_until_authorized_delivery_is_requested(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.manage', 'account_lifecycle.manage']);
        $role = Role::query()->where('school_id', $school->id)->firstOrFail();
        $token = $this->bearerTokenFor($admin);

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users', [
                'full_name' => 'Active Recipient',
                'email' => 'active-recipient@example.test',
                'role_ids' => [$role->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->json('data');

        Mail::assertNothingSent();

        $response = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$created['id']}/password-delivery")
            ->assertCreated()
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.delivery_channel', 'email')
            ->assertJsonStructure(['data' => ['status', 'delivery_channel', 'delivery_requested_at']]);

        $this->assertSame(
            ['status', 'delivery_channel', 'delivery_requested_at'],
            array_keys($response->json('data')),
        );

        /** @var PasswordDeliveryMail $mail */
        $mail = Mail::sent(PasswordDeliveryMail::class)->sole();
        $plainToken = basename((string) parse_url($mail->passwordUrl, PHP_URL_PATH));

        $this->assertTrue($mail->hasTo('active-recipient@example.test'));
        $this->assertNotSame('', $plainToken);
        $this->assertSame(hash('sha256', $plainToken), PasswordResetRequest::query()->sole()->token_hash);
        $this->assertStringNotContainsString($plainToken, $response->getContent());
        $this->assertStringNotContainsString('active-recipient@example.test', $response->getContent());
    }

    public function test_delivery_requires_authentication_valid_tenant_context_and_permission(): void
    {
        $school = School::factory()->create();
        $target = User::factory()->create(['school_id' => $school->id]);
        $authorized = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $limited = $this->createSchoolAdmin($school, []);

        $this->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertUnauthorized();

        $this->withToken($this->bearerTokenFor($authorized))
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($this->bearerTokenFor($limited))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        Mail::assertNothingSent();
    }

    public function test_tenant_is_resolved_before_target_lookup(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);
        $otherTarget = User::factory()->create(['school_id' => $otherSchool->id]);
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->postJson("/api/v1/users/{$otherTarget->uuid}/password-delivery")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$otherTarget->uuid}/password-delivery")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        Mail::assertNothingSent();
    }

    public function test_request_body_is_closed_and_validation_only_uses_422(): void
    {
        [$school, $admin, $target] = $this->schoolActors();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery", ['email' => $target->email])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonPath('error.details.fields.email.0', 'This field is not documented for this request.');

        Mail::assertNothingSent();
    }

    public function test_inactive_invited_deleted_and_locked_users_are_ineligible(): void
    {
        [$school, $admin] = $this->schoolActors(includeTarget: false);
        $token = $this->bearerTokenFor($admin);
        $targets = [
            User::factory()->create(['school_id' => $school->id, 'status' => 'inactive']),
            User::factory()->create(['school_id' => $school->id, 'status' => 'invited']),
        ];
        $deleted = User::factory()->create(['school_id' => $school->id]);
        $deleted->delete();
        $targets[] = $deleted;
        $locked = User::factory()->create(['school_id' => $school->id]);
        AccountLock::query()->create([
            'user_id' => $locked->id,
            'school_id' => $school->id,
            'actor_user_id' => $admin->id,
            'lock_type' => 'administrative',
            'status' => 'active',
            'locked_at' => now(),
        ]);
        $targets[] = $locked;

        foreach ($targets as $target) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
                ->assertConflict()
                ->assertJsonPath('error.code', 'conflict');
        }

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_delivery_is_limited_to_three_accepted_requests_per_user_and_scope_in_24_hours(): void
    {
        [$school, $admin, $target] = $this->schoolActors();
        $token = $this->bearerTokenFor($admin);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
                ->assertCreated();
        }

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'password_delivery_rate_limited');

        Mail::assertSentCount(3);
        $this->assertSame(3, PasswordResetRequest::query()->whereNotNull('delivery_requested_at')->count());
    }

    public function test_mail_failure_creates_no_usable_token_preserves_prior_link_and_allows_retry(): void
    {
        [$school, $admin, $target] = $this->schoolActors();
        $prior = $this->pendingReset($target, $school, 'prior-password-token');

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('smtp-provider-secret'));

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'temporary_unavailable')
            ->assertDontSee('smtp-provider-secret');

        $this->assertSame('pending', $prior->refresh()->status);
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'password_delivery_failed',
            'outcome' => 'failure',
            'affected_resource_id' => $target->uuid,
        ]);

        $this->app->forgetInstance('mail.manager');
        Mail::clearResolvedInstance('mail.manager');
        Mail::fake();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertCreated();

        $this->assertSame('superseded', $prior->refresh()->status);
        Mail::assertSentCount(1);
    }

    public function test_account_or_source_ip_completion_suppression_blocks_issuance(): void
    {
        [$school, $admin, $target] = $this->schoolActors();
        $suppressed = $this->pendingReset($target, $school, 'suppressed-password-token');
        $suppressed->forceFill(['suppressed_until' => now()->addMinutes(15)])->save();
        $token = $this->bearerTokenFor($admin);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'password_delivery_rate_limited');

        $suppressed->forceFill(['suppressed_until' => null])->save();
        LoginAttempt::query()->create([
            'attempt_key_type' => 'reset_token_ip',
            'attempt_key' => '127.0.0.1',
            'failed_attempt_count' => 5,
            'window_started_at' => now(),
            'locked_until' => now()->addMinutes(15),
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/users/{$target->uuid}/password-delivery")
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'password_delivery_rate_limited');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_platform_authority_can_deliver_only_to_platform_user_without_school_header(): void
    {
        $actor = $this->createPlatformUser(['account_lifecycle.manage']);
        $platformTarget = User::factory()->create(['school_id' => null]);
        $school = School::factory()->create();
        $schoolTarget = User::factory()->create(['school_id' => $school->id]);
        $token = $this->bearerTokenFor($actor);

        $this->withToken($token)
            ->postJson("/api/v1/users/{$platformTarget->uuid}/password-delivery")
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/v1/users/{$schoolTarget->uuid}/password-delivery")
            ->assertNotFound();

        Mail::assertSentCount(1);
    }

    /**
     * @return array{School, User, User}|array{School, User}
     */
    private function schoolActors(bool $includeTarget = true): array
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['account_lifecycle.manage']);

        if (! $includeTarget) {
            return [$school, $admin];
        }

        return [$school, $admin, User::factory()->create([
            'school_id' => $school->id,
            'status' => 'active',
        ])];
    }

    private function pendingReset(User $target, School $school, string $plainToken): PasswordResetRequest
    {
        return PasswordResetRequest::query()->create([
            'target_user_id' => $target->id,
            'school_id' => $school->id,
            'account_identifier_hash' => hash('sha256', strtolower($target->email).'|'.$school->id),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'delivery_requested_at' => now(),
            'delivery_channel' => 'email',
        ]);
    }
}
