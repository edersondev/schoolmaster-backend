<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_does_not_have_implicit_school_report_access(): void
    {
        $school = School::factory()->create();
        $platform = $this->createPlatformUser();

        $this->withToken($this->bearerTokenFor($platform))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/reports')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
