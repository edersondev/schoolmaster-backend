<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolContextSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_administrator_can_select_and_switch_between_active_schools(): void
    {
        $first = School::factory()->create();
        $second = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        foreach ([$first, $second] as $school) {
            $this->withToken($token)
                ->withHeader('X-School-Id', $school->uuid)
                ->getJson('/api/v1/users')
                ->assertOk();
        }
    }
}
