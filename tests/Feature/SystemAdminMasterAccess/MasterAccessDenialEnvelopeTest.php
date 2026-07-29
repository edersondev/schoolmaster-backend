<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MasterAccessDenialEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_tenant_identity_and_validation_denials_remain_distinct(): void
    {
        $school = School::factory()->create();
        $limitedToken = $this->bearerTokenFor($this->createLimitedPlatformUser());
        $masterToken = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($limitedToken)->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->flushHeaders();

        $this->withToken($masterToken)
            ->getJson('/api/v1/academic-years')
            ->assertForbidden();

        $this->withToken($masterToken)->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/student/grades')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->withToken($masterToken)->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years', [
                'name' => 'Invalid Year',
                'start_date' => '2026-12-31',
                'end_date' => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }
}
