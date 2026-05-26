<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AdministrationLifecyclePolicy
{
    public function view(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'view');
    }

    public function update(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'manage');
    }

    public function activate(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'lifecycle');
    }

    public function deactivate(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'lifecycle');
    }

    public function delete(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'lifecycle');
    }

    public function restore(User $user, Model $resource): bool
    {
        return $this->allowed($user, $resource, 'lifecycle');
    }

    public function bulk(User $user, School $school, string $resourceType): bool
    {
        return $user->hasSchoolPermission($this->permissionForResourceType($resourceType, 'lifecycle'), $school->id)
            || $user->hasSchoolPermission($this->permissionForResourceType($resourceType, 'manage'), $school->id);
    }

    private function allowed(User $user, Model $resource, string $action): bool
    {
        if ($resource instanceof School) {
            return $user->hasPermission($action === 'view' ? 'schools.view' : 'schools.lifecycle', 'platform')
                || $user->hasPermission($action === 'view' ? 'schools.view' : 'schools.manage', 'platform');
        }

        $schoolId = (int) $resource->getAttribute('school_id');

        return $user->hasSchoolPermission($this->permissionForModel($resource, $action), $schoolId)
            || ($action === 'lifecycle' && $user->hasSchoolPermission($this->permissionForModel($resource, 'manage'), $schoolId));
    }

    private function permissionForModel(Model $resource, string $action): string
    {
        return match (true) {
            $resource instanceof User => "users.$action",
            $resource instanceof Role => "roles.$action",
            $resource instanceof AcademicYear => "academic_years.$action",
            $resource instanceof AcademicPeriod => "academic_periods.$action",
            $resource instanceof Guardian => "guardians.$action",
            default => "unknown.$action",
        };
    }

    private function permissionForResourceType(string $resourceType, string $action): string
    {
        return match ($resourceType) {
            'users' => "users.$action",
            'roles' => "roles.$action",
            'academic_years' => "academic_years.$action",
            'academic_periods' => "academic_periods.$action",
            'guardians' => "guardians.$action",
            default => "unknown.$action",
        };
    }
}
