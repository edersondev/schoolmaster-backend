<?php

declare(strict_types=1);

namespace App\Services\Permissions;

use App\DTOs\TenantContext;
use App\Models\Permission;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolAdministration;
use App\Services\Concerns\ValidatesListQuery;
use App\Services\TenantContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PermissionQueryService
{
    use AuthorizesSchoolAdministration;
    use ValidatesListQuery;

    public function __construct(private readonly TenantContextService $tenantContext) {}

    public function list(User $actor, TenantContext $context, array $query): LengthAwarePaginator
    {
        $filters = $this->validateListQuery($query, allowedStatuses: []);

        if ($context->school === null) {
            if (! $actor->hasPermission('schools.view', 'platform')) {
                throw new AuthorizationException('The authenticated user lacks permission for this action.');
            }

            return Permission::query()
                ->where('scope', 'platform')
                ->where('status', 'active')
                ->orderBy('code')
                ->paginate((int) ($filters['per_page'] ?? 25));
        }

        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolPermission($actor, $school, 'permissions.view');

        return Permission::query()
            ->where('scope', 'school')
            ->where('status', 'active')
            ->orderBy('code')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }
}
