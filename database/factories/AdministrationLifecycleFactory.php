<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AdministrationLifecycleFactory
{
    public static function school(array $overrides = []): School
    {
        return School::factory()->create($overrides);
    }

    public static function user(School $school, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'school_id' => $school->id,
            'password' => Hash::make('password'),
            'status' => 'active',
        ], $overrides));
    }

    public static function role(School $school, array $overrides = []): Role
    {
        return Role::query()->create(array_merge([
            'school_id' => $school->id,
            'scope' => 'school',
            'name' => fake()->unique()->jobTitle(),
            'status' => 'active',
        ], $overrides));
    }

    public static function academicYear(School $school, array $overrides = []): AcademicYear
    {
        return AcademicYear::query()->create(array_merge([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ], $overrides));
    }

    public static function academicPeriod(School $school, AcademicYear $year, array $overrides = []): AcademicPeriod
    {
        return AcademicPeriod::query()->create(array_merge([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'sequence' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
        ], $overrides));
    }

    public static function guardian(School $school, array $overrides = []): Guardian
    {
        return Guardian::query()->create(array_merge([
            'school_id' => $school->id,
            'full_name' => fake()->name(),
            'relationship_type' => 'parent',
            'contact_email' => fake()->safeEmail(),
            'status' => 'active',
        ], $overrides));
    }
}
