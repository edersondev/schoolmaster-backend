<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProtectedOperationMasterAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_permission_system_administrator_can_read_every_released_protected_operation_group(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        foreach ($this->schoolScopedReadEndpoints() as $endpoint) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson($endpoint)
                ->assertOk();
        }

        foreach ($this->platformReadEndpoints() as $endpoint) {
            $this->withToken($token)->getJson($endpoint)->assertOk();
        }
    }

    /** @return list<string> */
    private function schoolScopedReadEndpoints(): array
    {
        return [
            '/api/v1/users',
            '/api/v1/roles',
            '/api/v1/permissions',
            '/api/v1/academic-years',
            '/api/v1/academic-periods',
            '/api/v1/guardians',
            '/api/v1/student-profiles',
            '/api/v1/class-sections',
            '/api/v1/teacher-assignments',
            '/api/v1/teacher-content',
            '/api/v1/questionnaires',
            '/api/v1/learning-sets',
            '/api/v1/grades',
            '/api/v1/attendance',
            '/api/v1/report-catalog',
            '/api/v1/report-definitions',
            '/api/v1/reports',
            '/api/v1/questionnaire-responses',
        ];
    }

    /** @return list<string> */
    private function platformReadEndpoints(): array
    {
        return [
            '/api/v1/schools',
            '/api/v1/platform/schools',
            '/api/v1/platform/reporting/overview',
            '/api/v1/platform/support-audit-events',
        ];
    }
}
