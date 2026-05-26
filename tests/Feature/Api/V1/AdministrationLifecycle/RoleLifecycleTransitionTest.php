<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Role;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_deactivate_unassigned_role(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['roles.view', 'roles.lifecycle']);
        $role = Role::query()->create(['school_id' => $school->id, 'scope' => 'school', 'name' => 'Archiveable']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson("/api/v1/roles/{$role->uuid}/deactivate", ['effective_at' => '2026-05-26', 'reason' => 'cleanup'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
