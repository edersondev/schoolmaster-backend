<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\Concerns\AuthorizesAdministrationLifecycle;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;

final class AdministrationDetailService
{
    use AuthorizesAdministrationLifecycle;

    public function __construct(
        private readonly AdministrationResourceRegistry $registry,
        private readonly TenantContextService $tenantContext,
    ) {}

    public function get(User $actor, ?TenantContext $context, string $resourceType, string $uuid): Model
    {
        $config = $this->registry->config($resourceType);
        $query = $config['model']::query()->with($config['relations'])->withTrashed();

        if (($config['platform_mode'] ?? false) && $context?->school === null) {
            $this->assertPlatformLifecyclePermission($actor, 'schools.view');
            $query->whereNull('school_id');
        } elseif ($config['scope'] === 'school') {
            $school = $this->tenantContext->requireSchool($context);
            $this->assertSchoolLifecyclePermission($actor, $school, "{$config['permission']}.view");
            $query->where('school_id', $school->id);
        } else {
            $this->assertPlatformLifecyclePermission($actor, 'schools.view');
        }

        return $query->where('uuid', $uuid)->firstOrFail();
    }
}
