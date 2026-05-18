<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_create_and_list_school_role(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $permission = Permission::query()->where('code', 'users.view')->firstOrFail();

        $created = $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/roles', [
                'scope' => 'school',
                'name' => 'Registrar',
                'permission_ids' => [$permission->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Registrar')
            ->assertJsonPath('data.school_id', $school->uuid)
            ->json('data');

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']]);
    }

    public function test_school_role_rejects_platform_permission(): void
    {
        $school = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        $platformPermission = Permission::query()->firstOrCreate([
            'code' => 'platform.only',
        ], [
            'name' => 'Platform Only',
            'scope' => 'platform',
        ]);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/roles', [
                'scope' => 'school',
                'name' => 'Invalid Role',
                'permission_ids' => [$platformPermission->uuid],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_roles_are_tenant_scoped(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $token = $this->bearerTokenFor($this->createSchoolAdmin($school));
        Role::query()->create(['school_id' => $otherSchool->id, 'scope' => 'school', 'name' => 'Other School Role']);

        $this->withToken($token)
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Other School Role']);
    }

    public function test_platform_user_can_create_platform_role_without_school_context(): void
    {
        $token = $this->bearerTokenFor($this->createPlatformUser());
        $permission = Permission::query()->where('code', 'schools.view')->firstOrFail();

        $this->withToken($token)
            ->postJson('/api/v1/roles', [
                'scope' => 'platform',
                'name' => 'Platform Auditor',
                'permission_ids' => [$permission->uuid],
            ])
            ->assertCreated()
            ->assertJsonPath('data.scope', 'platform')
            ->assertJsonPath('data.school_id', null);
    }
}
