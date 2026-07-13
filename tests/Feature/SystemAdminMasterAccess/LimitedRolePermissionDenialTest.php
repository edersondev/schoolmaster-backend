<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LimitedRolePermissionDenialTest extends TestCase
{
    use RefreshDatabase;

    public function test_limited_platform_user_remains_denied_across_protected_operation_groups(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createLimitedPlatformUser());
        $endpoints = [
            '/api/v1/users',
            '/api/v1/roles',
            '/api/v1/academic-years',
            '/api/v1/guardians',
            '/api/v1/student-profiles',
            '/api/v1/class-sections',
            '/api/v1/teacher-content',
            '/api/v1/questionnaires',
            '/api/v1/learning-sets',
            '/api/v1/grades',
            '/api/v1/reports',
        ];

        foreach ($endpoints as $endpoint) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson($endpoint)
                ->assertForbidden()
                ->assertJsonPath('error.code', 'forbidden');
        }

        foreach (['/api/v1/schools', '/api/v1/platform/schools', '/api/v1/platform/reporting/overview'] as $endpoint) {
            $this->withToken($token)
                ->getJson($endpoint)
                ->assertForbidden();
        }
    }
}
