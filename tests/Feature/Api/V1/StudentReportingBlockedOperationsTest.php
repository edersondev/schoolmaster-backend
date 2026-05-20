<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentReportingBlockedOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_student_reporting_routes_are_not_exposed(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->deleteJson('/api/v1/reports/report-id')
            ->assertNotFound();

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/reports/report-id/retry')
            ->assertNotFound();
    }
}
