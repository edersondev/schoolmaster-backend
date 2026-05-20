<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentReportingResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_reporting_errors_use_documented_envelope(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['reports.view']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports/not-a-uuid/download?format=pdf')
            ->assertNotFound()
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }
}
