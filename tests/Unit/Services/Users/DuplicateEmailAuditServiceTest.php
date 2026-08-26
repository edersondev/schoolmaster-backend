<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Users;

use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use App\Services\Users\DuplicateEmailAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DuplicateEmailAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_one_allowlisted_recoverable_audit_with_target(): void
    {
        $school = School::factory()->create();
        $actor = User::factory()->create(['school_id' => $school->id]);
        $target = User::factory()->create(['school_id' => $school->id]);

        app(DuplicateEmailAuditService::class)->record(
            actor: $actor,
            school: $school,
            scope: 'school',
            workflow: 'direct_user_creation',
            outcome: 'recoverable_user_conflict',
            canonicalEmail: 'joao@teste.com.br',
            reasonCode: 'recoverable_user_conflict',
            sourceIp: '203.0.113.10',
            target: $target,
        );

        $event = AuditEvent::query()->sole();
        $this->assertSame('user_creation_duplicate_email', $event->event_type);
        $this->assertSame($actor->id, $event->actor_user_id);
        $this->assertSame($school->id, $event->school_id);
        $this->assertSame('user', $event->affected_resource_type);
        $this->assertSame($target->uuid, $event->affected_resource_id);
        $this->assertSame('recoverable_user_conflict', $event->outcome);
        $this->assertSame('203.0.113.10', $event->source_ip);
        $this->assertSame([
            'scope' => 'school',
            'workflow' => 'direct_user_creation',
            'email_hash' => hash('sha256', 'joao@teste.com.br'),
            'reason_code' => 'recoverable_user_conflict',
        ], $event->tenant_safe_metadata);
        $this->assertStringNotContainsString('joao@teste.com.br', $event->getRawOriginal('tenant_safe_metadata'));
    }

    public function test_generic_audit_never_records_target_or_plaintext_email(): void
    {
        $actor = User::factory()->create(['school_id' => null]);
        $hiddenTarget = User::factory()->create(['school_id' => null]);

        app(DuplicateEmailAuditService::class)->record(
            actor: $actor,
            school: null,
            scope: 'platform',
            workflow: 'account_invitation',
            outcome: 'validation_failed',
            canonicalEmail: 'hidden@example.test',
            reasonCode: 'email_unavailable',
            target: $hiddenTarget,
        );

        $event = AuditEvent::query()->sole();
        $this->assertNull($event->affected_resource_type);
        $this->assertNull($event->affected_resource_id);
        $this->assertSame('validation_failed', $event->outcome);
        $this->assertStringNotContainsString('hidden@example.test', $event->getRawOriginal('tenant_safe_metadata'));
    }
}
