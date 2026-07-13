<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolInstitutionalLookup;
use App\Models\User;
use App\Services\AuthTokenLifecycleService;
use Database\Seeders\SchoolInstitutionalLookupSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    private static int $schoolProfileSequence = 1000;

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

    protected function createSystemAdministrator(): User
    {
        $user = User::factory()->create([
            'school_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'school_id' => null,
            'scope' => 'platform',
            'name' => 'System Administrator',
            'status' => 'active',
        ]);

        $user->roles()->attach($role);

        return $user->refresh()->load('roles.permissions');
    }

    protected function createLimitedPlatformUser(): User
    {
        return $this->createPlatformUser([]);
    }

    protected function createSchoolAdmin(School $school, array $permissions = [
        'users.view',
        'users.manage',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'academic_years.view',
        'academic_years.manage',
        'academic_periods.view',
        'academic_periods.manage',
        'guardians.view',
        'guardians.manage',
    ]): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Test School Administrator',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::query()->firstOrCreate([
                'code' => $permission,
            ], [
                'name' => str_replace('.', ' ', $permission),
                'scope' => 'school',
            ]));
        }

        $user->roles()->attach($role);

        return $user->refresh()->load('roles.permissions');
    }

    protected function createTeacher(School $school, array $permissions = [
        'teacher_content.view',
        'teacher_content.manage',
        'questionnaires.view',
        'questionnaires.manage',
        'learning_sets.view',
        'learning_sets.manage',
        'grades.view',
        'grades.manage',
        'attendance.view',
        'attendance.manage',
    ]): User
    {
        $user = User::factory()->create([
            'school_id' => $school->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => 'Test Teacher',
        ]);

        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::query()->firstOrCreate([
                'code' => $permission,
            ], [
                'name' => str_replace('.', ' ', $permission),
                'scope' => 'school',
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validSchoolProfilePayload(array $overrides = []): array
    {
        $this->seedSchoolInstitutionalLookups();
        $sequence = self::$schoolProfileSequence++;

        return array_replace_recursive([
            'inep_code' => str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'status' => 1,
            'name' => 'Test School '.$sequence,
            'trade_name' => 'Test School',
            'legal_name' => 'Test School Legal Name',
            'document' => $this->validCnpj($sequence),
            'email' => 'school'.$sequence.'@example.com',
            'phone' => '11999990000',
            'website' => 'https://school'.$sequence.'.example.com',
            'description' => 'School profile test fixture.',
            'address' => [
                'street' => 'Main Street',
                'number' => '123',
                'complement' => null,
                'neighborhood' => 'Central',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '12345678',
                'country' => 'Brazil',
            ],
            'administrative_type_id' => 1,
            'legal_nature_id' => 1,
            'management_type_id' => 1,
            'pedagogical_approach_id' => 1,
            'education_level_ids' => [1],
            'modality_ids' => [1],
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'primary_color' => '#1D4ED8',
            'secondary_color' => '#F59E0B',
        ], $overrides);
    }

    protected function seedSchoolInstitutionalLookups(): void
    {
        if (SchoolInstitutionalLookup::query()->exists()) {
            return;
        }

        $this->seed(SchoolInstitutionalLookupSeeder::class);
    }

    private function validCnpj(int $sequence): string
    {
        $base = str_pad((string) $sequence, 8, '0', STR_PAD_LEFT).'0001';
        $firstDigit = $this->cnpjCheckDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->cnpjCheckDigit($base.$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $base.$firstDigit.$secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function cnpjCheckDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
