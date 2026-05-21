<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_downloads_generated_report_output(): void
    {
        Storage::fake('report_outputs');
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::reportRun($school, $admin, ['status' => 'generated', 'outputs_available' => true, 'generated_at' => now(), 'output_expires_at' => now()->addDays(90)]);
        $output = StudentReportingFactory::reportOutput($school, $run);
        Storage::disk('report_outputs')->put($output->storage_path, 'pdf');

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->get('/api/v1/reports/'.$run->uuid.'/download?format=pdf')
            ->assertOk();
    }
}
