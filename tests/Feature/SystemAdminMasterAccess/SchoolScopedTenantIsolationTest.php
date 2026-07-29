<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchoolScopedTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_scoped_master_queries_return_only_selected_school_data(): void
    {
        $first = School::factory()->create();
        $second = School::factory()->create();
        $firstUser = User::factory()->create(['school_id' => $first->id, 'email' => 'first-tenant@example.test']);
        $secondUser = User::factory()->create(['school_id' => $second->id, 'email' => 'second-tenant@example.test']);
        $token = $this->bearerTokenFor($this->createSystemAdministrator());

        $this->withToken($token)
            ->withHeader('X-School-Id', $first->uuid)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $firstUser->uuid])
            ->assertJsonMissing(['id' => $secondUser->uuid]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $second->uuid)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $secondUser->uuid])
            ->assertJsonMissing(['id' => $firstUser->uuid]);
    }
}
