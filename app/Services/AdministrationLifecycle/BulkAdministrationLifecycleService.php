<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\DTOs\AdministrationLifecycle\ApplyBulkLifecycleActionData;
use App\DTOs\AdministrationLifecycle\ApplyLifecycleTransitionData;
use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\Concerns\AuthorizesAdministrationLifecycle;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BulkAdministrationLifecycleService
{
    use AuthorizesAdministrationLifecycle;

    public function __construct(
        private readonly AdministrationResourceRegistry $registry,
        private readonly TenantContextService $tenantContext,
        private readonly AdministrationLifecycleService $lifecycle,
    ) {}

    public function apply(User $actor, TenantContext $context, ApplyBulkLifecycleActionData $data): BulkAdministrationLifecycleResult
    {
        $config = $this->registry->config($data->resourceType);

        if ($config['scope'] !== 'school') {
            throw ValidationException::withMessages(['resource_type' => ['Bulk lifecycle is approved only for school-owned resources.']]);
        }

        $school = $this->tenantContext->requireSchool($context);
        $this->assertSchoolLifecyclePermission($actor, $school, "{$config['permission']}.lifecycle");

        if (count($data->recordIds) !== count(array_unique($data->recordIds))) {
            throw ValidationException::withMessages(['record_ids' => ['Record identifiers must be unique.']]);
        }

        $resources = $config['model']::query()
            ->withTrashed()
            ->where('school_id', $school->id)
            ->whereIn('uuid', $data->recordIds)
            ->get()
            ->keyBy('uuid');

        if ($resources->count() !== count($data->recordIds)) {
            throw ValidationException::withMessages(['record_ids' => ['Every selected record must exist in the resolved school scope.']]);
        }

        $transition = new ApplyLifecycleTransitionData($data->effectiveAt, $data->reason);

        return DB::transaction(function () use ($actor, $resources, $data, $transition): BulkAdministrationLifecycleResult {
            $results = [];

            foreach ($data->recordIds as $recordId) {
                $results[] = $this->lifecycle->applyToResource($actor, $resources[$recordId], $data->action, $transition);
            }

            return new BulkAdministrationLifecycleResult($data->resourceType, $data->action, $results);
        });
    }
}
