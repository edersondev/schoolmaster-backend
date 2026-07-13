<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\AuditEvent;
use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MasterAccessReadAuditBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_navigation_creates_no_master_access_marker(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson('/api/v1/users')->assertOk();
        $this->withToken($token)->getJson('/api/v1/platform/schools')->assertOk();

        $this->assertSame(0, AuditEvent::query()->where('tenant_safe_metadata->master_access_used', true)->count());
        $this->assertSame(0, PlatformSupportAuditEvent::query()->where('metadata->master_access_used', true)->count());
    }
}
