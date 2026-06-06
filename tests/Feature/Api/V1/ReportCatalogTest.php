<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_catalog_returns_launch_scope_domains_without_hidden_fields(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/report-catalog')
            ->assertOk()
            ->assertJsonPath('data.domains.0.id', 'attendance');

        $this->assertStringNotContainsString('credential', $response->getContent());
        $this->assertStringNotContainsString('private_path', $response->getContent());
    }
}
