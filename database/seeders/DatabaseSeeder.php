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

        $permissions = collect(['schools.view', 'schools.manage'])->mapWithKeys(function (string $code): array {
            $permission = Permission::query()->where('code', $code)->firstOrFail();

            return [$code => $permission];
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
