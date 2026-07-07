<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(SchoolInstitutionalLookupSeeder::class);

        $permissions = Permission::query()
            ->whereIn('code', [
                'schools.view',
                'schools.manage',
                'schools.lifecycle',
                'platform_support.overview',
                'platform_support.reporting',
                'platform_support.drill_down',
                'platform_support.approve',
                'platform_support.audit',
            ])
            ->get();

        $role = Role::query()->firstOrCreate([
            'scope' => 'platform',
            'name' => 'System Administrator',
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $user = User::query()->updateOrCreate([
            'email' => 'admin@schoolmaster.local',
        ], [
            'name' => 'System Administrator',
            'full_name' => 'System Administrator',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
