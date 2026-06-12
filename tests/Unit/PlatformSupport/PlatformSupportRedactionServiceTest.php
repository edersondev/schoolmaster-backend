<?php

declare(strict_types=1);

namespace Tests\Unit\PlatformSupport;

use App\Services\PlatformSupport\PlatformSupportRedactionService;
use PHPUnit\Framework\TestCase;

final class PlatformSupportRedactionServiceTest extends TestCase
{
    public function test_protected_counts_below_five_are_suppressed(): void
    {
        $service = new PlatformSupportRedactionService;

        $this->assertSame(['value' => null, 'suppressed' => true], $service->protectedCount(4));
        $this->assertSame(['value' => 5, 'suppressed' => false], $service->protectedCount(5));
        $this->assertSame(['value' => 0, 'suppressed' => false], $service->protectedCount(0));
    }

    public function test_audit_metadata_removes_protected_fields(): void
    {
        $service = new PlatformSupportRedactionService;

        $this->assertSame(
            ['status' => 'allowed'],
            $service->auditMetadata([
                'status' => 'allowed',
                'bearer_token' => 'secret',
                'private_file_path' => '/private/report.pdf',
                'student_record' => 'hidden',
                'nested' => ['not' => 'scalar'],
            ]),
        );
    }
}
