<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AuditEventService;
use Tests\TestCase;

final class AuditEventServiceTest extends TestCase
{
    public function test_sanitizes_sensitive_audit_metadata(): void
    {
        $metadata = app(AuditEventService::class)->sanitize([
            'email' => 'user@example.com',
            'password' => 'secret',
            'token' => 'plain-token',
        ]);

        $this->assertSame(['email' => 'user@example.com'], $metadata);
    }
}
