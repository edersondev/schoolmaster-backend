<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditEventApiBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_and_school_lifecycle_events_are_recorded(): void
    {
        $user = $this->createPlatformUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'bad-password',
        ])->assertUnauthorized();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $created = $this->withToken($login->json('data.token'))->postJson('/api/v1/schools', [
            'name' => 'Audit School',
            'code' => 'AUDIT',
        ])->assertCreated()->json('data');

        $this->withToken($login->json('data.token'))->patchJson('/api/v1/schools/'.$created['id'], [
            'status' => 'inactive',
        ])->assertOk();

        $this->withToken($login->json('data.token'))->postJson('/api/v1/auth/logout')->assertOk();
        $this->withToken($login->json('data.token'))->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->assertTrue(AuditEvent::query()->where('event_type', 'login_failure')->exists());
        $this->assertTrue(AuditEvent::query()->where('event_type', 'login_success')->exists());
        $this->assertTrue(AuditEvent::query()->where('event_type', 'school_created')->exists());
        $this->assertTrue(AuditEvent::query()->where('event_type', 'school_deactivated')->exists());
        $this->assertTrue(AuditEvent::query()->where('event_type', 'logout')->exists());
        $this->assertTrue(AuditEvent::query()->where('event_type', 'token_rejected')->exists());
    }
}
