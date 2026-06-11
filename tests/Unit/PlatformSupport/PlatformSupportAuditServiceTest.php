<?php

declare(strict_types=1);

namespace Tests\Unit\PlatformSupport;

use App\Models\School;
use App\Services\PlatformSupport\PlatformSupportAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformSupportAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_redacts_sensitive_metadata_before_storage(): void
    {
        $school = School::factory()->create();
        $actor = $this->createPlatformUser(['platform_support.audit']);

        $event = app(PlatformSupportAuditService::class)->record(
            actor: $actor,
            action: 'denied_access',
            outcome: 'denied',
            reasonCode: 'access_denied',
            correlationId: 'case-redact',
            school: $school,
            metadata: [
                'status' => 'denied',
                'credential' => 'secret',
                'raw_report_output' => 'hidden',
                'private_path' => '/private/file',
                'student_record' => 'hidden',
            ],
        );

        $this->assertSame(['status' => 'denied'], $event->metadata);
    }
}
