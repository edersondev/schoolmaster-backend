<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportOutputExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_report_output_returns_gone(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::reportRun($school, $admin, ['status' => 'generated', 'outputs_available' => false, 'generated_at' => now()->subDays(91), 'output_expires_at' => now()->subDay()]);
        StudentReportingFactory::reportOutput($school, $run, 'pdf', ['generated_at' => now()->subDays(91), 'expires_at' => now()->subDay()]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports/'.$run->uuid.'/download?format=pdf')
            ->assertStatus(410)
            ->assertJsonPath('error.code', 'output_expired');
    }
}
