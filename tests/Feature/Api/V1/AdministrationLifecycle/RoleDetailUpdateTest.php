<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Role;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleDetailUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_view_and_update_same_school_role(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['roles.view', 'roles.manage']);
        $role = Role::query()->create(['school_id' => $school->id, 'scope' => 'school', 'name' => 'Old Role']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->patchJson("/api/v1/roles/{$role->uuid}", ['name' => 'New Role'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Role');
    }
}
