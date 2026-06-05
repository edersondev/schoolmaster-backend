<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportOutputAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_list_exposes_per_format_availability_without_deleted_state(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin, ['output_formats' => ['pdf', 'csv', 'xlsx']]);
        StudentReportingFactory::availableReportOutput($school, $run, 'pdf');
        StudentReportingFactory::failedReportOutput($school, $run, 'csv');
        StudentReportingFactory::unsupportedReportOutput($school, $run, 'xlsx');

        $response = $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports')
            ->assertOk();

        $this->assertStringContainsString('unsupported', $response->getContent());
        $this->assertStringNotContainsString('"deleted"', $response->getContent());
    }
}
