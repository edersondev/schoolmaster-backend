<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserSystemAdministratorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_platform_system_administrator_satisfies_platform_and_school_permissions_without_assignments(): void
    {
        $actor = $this->createSystemAdministrator();

        $this->assertTrue($actor->isSystemAdministrator());
        $this->assertTrue($actor->hasPermission('schools.manage', 'platform'));
        $this->assertTrue($actor->hasSchoolPermission('users.manage', 999));
        $this->assertCount(0, $actor->roles->flatMap->permissions);
    }

    public function test_role_name_match_is_exact(): void
    {
        $actor = $this->userWithRole('system administrator');

        $this->assertFalse($actor->isSystemAdministrator());
        $this->assertFalse($actor->hasPermission('schools.manage', 'platform'));
    }

    public function test_school_scoped_role_with_master_name_is_not_system_administrator(): void
    {
        $school = School::factory()->create();
        $actor = $this->userWithRole('System Administrator', 'school', $school);

        $this->assertFalse($actor->isSystemAdministrator());
        $this->assertFalse($actor->hasSchoolPermission('users.manage', $school->id));
    }

    public function test_inactive_or_deleted_master_role_does_not_override_permissions(): void
    {
        $inactive = $this->userWithRole('System Administrator', status: 'inactive');
        $deleted = $this->userWithRole('System Administrator');
        $deleted->roles->firstOrFail()->delete();

        $this->assertFalse($inactive->isSystemAdministrator());
        $this->assertFalse($deleted->fresh()->isSystemAdministrator());
    }

    public function test_limited_role_permission_behavior_is_unchanged(): void
    {
        $permission = Permission::query()->create([
            'code' => 'schools.view',
            'name' => 'View schools',
            'scope' => 'platform',
            'status' => 'active',
        ]);
        $actor = $this->userWithRole('Limited Platform User');
        $actor->roles->firstOrFail()->permissions()->attach($permission);

        $this->assertFalse($actor->isSystemAdministrator());
        $this->assertTrue($actor->hasPermission('schools.view', 'platform'));
        $this->assertFalse($actor->hasPermission('schools.manage', 'platform'));
        $this->assertFalse($actor->hasSchoolPermission('users.manage', 999));
    }

    private function userWithRole(
        string $name,
        string $scope = 'platform',
        ?School $school = null,
        string $status = 'active',
    ): User {
        $actor = User::factory()->create([
            'school_id' => $school?->id,
            'status' => 'active',
        ]);
        $role = Role::query()->create([
            'school_id' => $school?->id,
            'scope' => $scope,
            'name' => $name,
            'status' => $status,
        ]);
        $actor->roles()->attach($role);

        return $actor->refresh()->load('roles.permissions');
    }
}
