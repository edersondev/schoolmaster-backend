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
        $permissions = collect([
            ['code' => 'schools.view', 'name' => 'View schools', 'scope' => 'platform'],
            ['code' => 'schools.manage', 'name' => 'Manage schools', 'scope' => 'platform'],
        ])->mapWithKeys(function (array $data): array {
            $permission = Permission::query()->firstOrCreate(['code' => $data['code']], $data);

            return [$data['code'] => $permission];
        });

        $role = Role::query()->firstOrCreate([
            'scope' => 'platform',
            'name' => 'System Administrator',
        ]);
        $role->permissions()->sync($permissions->pluck('id')->all());

        $user = User::query()->firstOrCreate([
            'email' => 'admin@schoolmaster.local',
        ], [
            'name' => 'System Administrator',
            'full_name' => 'System Administrator',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
