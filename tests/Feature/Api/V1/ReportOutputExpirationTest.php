<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportOutputExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_output_download_returns_expired_response_without_regeneration(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin);
        $output = StudentReportingFactory::expiredReportOutput($school, $run, 'pdf');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson("/api/v1/reports/{$run->uuid}/download?format=pdf")
            ->assertGone();

        $this->assertDatabaseHas('report_outputs', [
            'id' => $output->id,
            'availability' => 'expired',
        ]);
    }
}
