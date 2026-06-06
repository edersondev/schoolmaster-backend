<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\StudentReportingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportRunListTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_lists_same_school_report_runs(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::reportRun($school, $admin);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonPath('data.0.id', $run->uuid);
    }

    public function test_include_deleted_false_does_not_return_soft_deleted_runs(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);
        $run = StudentReportingFactory::generatedReportRun($school, $admin);
        $run->delete();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports?include_deleted=false')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
