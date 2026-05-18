<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PermissionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_list_school_permissions(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'scope', 'status']], 'meta' => ['page', 'per_page', 'total']])
            ->assertJsonPath('data.0.scope', 'school');
    }
}
