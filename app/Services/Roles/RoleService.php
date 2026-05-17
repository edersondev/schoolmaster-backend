<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\DTOs\Roles\CreateRoleData;
use App\DTOs\TenantContext;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RoleService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQuery($query);

        if ($context->school === null) {
            if (! $actor->hasPermission('schools.view', 'platform')) {
                throw new AuthorizationException('The authenticated user lacks permission for this action.');
            }

            return Role::query()
                ->with(['permissions', 'school'])
                ->where('scope', 'platform')
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->orderBy('name')
                ->paginate((int) ($filters['per_page'] ?? 25));
        }

        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'roles.view');

        return Role::query()
            ->with(['permissions', 'school'])
            ->where('scope', 'school')
            ->where('school_id', $school->id)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(User $actor, TenantContext $context, CreateRoleData $data): Role
    {
        if ($data->scope === 'platform') {
            return $this->createPlatformRole($actor, $context, $data);
        }

        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'roles.manage');

        if ($data->schoolId !== null && $data->schoolId !== $school->uuid) {
            throw ValidationException::withMessages(['school_id' => ['The school_id must match the resolved tenant context.']]);
        }

        $permissions = $this->activePermissions($data->permissionIds, 'school');

        return DB::transaction(function () use ($data, $school, $permissions): Role {
            $role = Role::query()->create([
                'school_id' => $school->id,
                'scope' => 'school',
                'name' => $data->name,
                'status' => 'active',
            ]);
            $role->permissions()->sync($permissions->pluck('id')->all());

            return $role->load(['permissions', 'school']);
        });
    }

    private function createPlatformRole(User $actor, TenantContext $context, CreateRoleData $data): Role
    {
        if ($context->school !== null) {
            throw ValidationException::withMessages(['scope' => ['Platform roles cannot be created through a school tenant context.']]);
        }

        if ($data->schoolId !== null) {
            throw ValidationException::withMessages(['school_id' => ['Platform roles cannot include a school_id.']]);
        }

        if (! $actor->hasPermission('schools.manage', 'platform')) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }

        $permissions = $this->activePermissions($data->permissionIds, 'platform');

        return DB::transaction(function () use ($data, $permissions): Role {
            $role = Role::query()->create([
                'scope' => 'platform',
                'name' => $data->name,
                'status' => 'active',
            ]);
            $role->permissions()->sync($permissions->pluck('id')->all());

            return $role->load(['permissions', 'school']);
        });
    }

    /**
     * @param  array<int, string>  $permissionUuids
     * @return Collection<int, Permission>
     */
    public function activePermissions(array $permissionUuids, string $scope): Collection
    {
        $permissions = Permission::query()
            ->whereIn('uuid', $permissionUuids)
            ->where('status', 'active')
            ->where('scope', $scope)
            ->get();

        if ($permissions->count() !== count(array_unique($permissionUuids))) {
            throw ValidationException::withMessages([
                'permission_ids' => ['All permissions must exist, be active, and match the requested scope.'],
            ]);
        }

        return $permissions;
    }
}
