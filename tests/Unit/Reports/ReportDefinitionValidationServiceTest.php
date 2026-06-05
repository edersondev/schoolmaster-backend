<?php

declare(strict_types=1);

namespace Tests\Unit\Reports;

use App\DTOs\Reports\ReportDefinitionData;
use App\Services\Reports\ReportCatalogService;
use App\Services\Reports\ReportDefinitionValidationService;
use App\Services\Reports\ReportTenantContextService;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReportDefinitionValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsupported_catalog_field_is_rejected(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.definitions.manage']);
        $context = app(ReportTenantContextService::class)->resolve($admin, new \App\DTOs\TenantContext($school, 'test', 'resolved'));
        $catalog = app(ReportCatalogService::class)->catalog($context);

        $this->expectException(ValidationException::class);

        app(ReportDefinitionValidationService::class)->validateCatalog(new ReportDefinitionData(
            name: 'Bad Report',
            description: null,
            domain: 'attendance',
            fields: ['private_path'],
            filters: [],
            grouping: [],
            sorting: [],
            outputFormats: ['pdf'],
        ), $catalog);
    }
}
