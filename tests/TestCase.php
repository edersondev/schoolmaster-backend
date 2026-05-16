<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthTokenLifecycleService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function createPlatformUser(array $permissions = ['schools.view', 'schools.manage']): User
    {
        $user = User::factory()->create([
            'school_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'scope' => 'platform',
            'name' => 'Test Platform Role',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::query()->firstOrCreate([
                'code' => $permission,
            ], [
                'name' => str_replace('.', ' ', $permission),
                'scope' => 'platform',
            ]));
        }

        $user->roles()->attach($role);

        return $user->refresh()->load('roles.permissions');
    }

    protected function bearerTokenFor(User $user): string
    {
        [$token] = app(AuthTokenLifecycleService::class)->issue($user);

        return $token;
    }
}
