<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformWideOperationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_documented_platform_operations_return_cross_school_output(): void
    {
        $first = School::factory()->create(['name' => 'Master Scope First']);
        $second = School::factory()->create(['name' => 'Master Scope Second']);
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)
            ->getJson('/api/v1/platform/schools')
            ->assertOk()
            ->assertJsonFragment(['name' => $first->name])
            ->assertJsonFragment(['name' => $second->name]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $first->uuid)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonMissing(['school_id' => $second->uuid]);
    }
}
