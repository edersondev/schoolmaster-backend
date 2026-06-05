<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportTenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_catalog_rejects_mismatched_school_context(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/report-catalog')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }

    public function test_report_lifecycle_rejects_missing_school_context_for_platform_user(): void
    {
        $platformUser = $this->createPlatformUser();

        $this->withToken($this->bearerTokenFor($platformUser))
            ->postJson('/api/v1/reports/'.fake()->uuid().'/cancel', ['reason_code' => 'no_longer_needed'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
