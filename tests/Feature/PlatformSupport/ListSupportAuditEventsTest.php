<?php

declare(strict_types=1);

namespace Tests\Feature\PlatformSupport;

use App\Models\School;
use App\Services\PlatformSupport\PlatformSupportAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListSupportAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_platform_actor_can_list_minimized_support_audit_events(): void
    {
        $school = School::factory()->create();
        $actor = $this->createPlatformUser(['platform_support.audit']);

        app(PlatformSupportAuditService::class)->record(
            actor: $actor,
            action: 'support_escalation',
            outcome: 'allowed',
            reasonCode: 'support_case',
            correlationId: 'case-audit',
            school: $school,
            metadata: ['status' => 'allowed', 'bearer_token' => 'secret'],
        );

        $this->withToken($this->bearerTokenFor($actor))
            ->getJson('/api/v1/platform/support-audit-events')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'support_escalation')
            ->assertJsonPath('data.0.target_school_id', $school->uuid)
            ->assertJsonPath('data.0.metadata.status', 'allowed')
            ->assertJsonMissingPath('data.0.metadata.bearer_token');
    }
}
