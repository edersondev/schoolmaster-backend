<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdministrationLifecycleBlockedOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjacent_blocked_operations_are_not_registered(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        foreach ([
            '/api/v1/invitations',
            '/api/v1/password-reset',
            '/api/v1/rosters',
            '/api/v1/report-outputs',
            '/api/v1/support/schools',
        ] as $path) {
            $this->withToken($token)->withHeader('X-School-Id', $school->uuid)->getJson($path)->assertNotFound();
        }
    }
}
