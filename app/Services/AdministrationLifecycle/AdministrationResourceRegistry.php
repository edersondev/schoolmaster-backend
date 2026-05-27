<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class AdministrationResourceRegistry
{
    /**
     * @return array<string, mixed>
     */
    public function config(string $resourceType): array
    {
        return match ($resourceType) {
            'schools' => [
                'model' => School::class,
                'permission' => 'schools',
                'scope' => 'platform',
                'relations' => [],
                'mutable' => ['name', 'status', 'contact_email', 'contact_phone', 'address_summary'],
            ],
            'users' => [
                'model' => User::class,
                'permission' => 'users',
                'scope' => 'school',
                'relations' => ['school', 'roles.permissions', 'roles.school'],
                'mutable' => ['full_name', 'email', 'status'],
            ],
            'roles' => [
                'model' => Role::class,
                'permission' => 'roles',
                'scope' => 'school',
                'relations' => ['school', 'permissions'],
                'mutable' => ['name', 'status'],
            ],
            'academic_years' => [
                'model' => AcademicYear::class,
                'permission' => 'academic_years',
                'scope' => 'school',
                'relations' => ['school'],
                'mutable' => ['name', 'start_date', 'end_date', 'status'],
            ],
            'academic_periods' => [
                'model' => AcademicPeriod::class,
                'permission' => 'academic_periods',
                'scope' => 'school',
                'relations' => ['school', 'academicYear'],
                'mutable' => ['name', 'sequence', 'start_date', 'end_date', 'status'],
            ],
            'guardians' => [
                'model' => Guardian::class,
                'permission' => 'guardians',
                'scope' => 'school',
                'relations' => ['school'],
                'mutable' => ['full_name', 'relationship_type', 'contact_email', 'contact_phone', 'status'],
            ],
            default => throw new InvalidArgumentException("Unsupported administration lifecycle resource [$resourceType]."),
        };
    }

    public function resourceTypeForModel(Model $resource): string
    {
        return match (true) {
            $resource instanceof School => 'schools',
            $resource instanceof User => 'users',
            $resource instanceof Role => 'roles',
            $resource instanceof AcademicYear => 'academic_years',
            $resource instanceof AcademicPeriod => 'academic_periods',
            $resource instanceof Guardian => 'guardians',
            default => throw new InvalidArgumentException('Unsupported administration lifecycle resource.'),
        };
    }
}
