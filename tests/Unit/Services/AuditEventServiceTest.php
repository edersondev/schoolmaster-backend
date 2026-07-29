<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\AuditEventData;
use App\Services\AuditEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditEventServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_access_marker_is_canonical_and_cannot_be_spoofed(): void
    {
        $service = app(AuditEventService::class);
        $marked = $service->record(new AuditEventData(
            eventType: 'test.mutation',
            outcome: 'success',
            metadata: ['master_access_used' => false, 'safe' => 'value'],
            masterAccessUsed: true,
        ));
        $unmarked = $service->record(new AuditEventData(
            eventType: 'test.read',
            outcome: 'success',
            metadata: ['master_access_used' => true],
        ));

        $this->assertTrue($marked->tenant_safe_metadata['master_access_used']);
        $this->assertSame('value', $marked->tenant_safe_metadata['safe']);
        $this->assertArrayNotHasKey('master_access_used', $unmarked->tenant_safe_metadata);
    }
}
