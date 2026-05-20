<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentReportingTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_listing_rejects_mismatched_tenant_context(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/reports')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'tenant_mismatch');
    }
}
