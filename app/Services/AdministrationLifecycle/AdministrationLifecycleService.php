<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\DTOs\AdministrationLifecycle\ApplyLifecycleTransitionData;
use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\AdministrationLifecycle\DependencyChecks\AcademicPeriodLifecycleDependencyCheck;
use App\Services\AdministrationLifecycle\DependencyChecks\AcademicYearLifecycleDependencyCheck;
use App\Services\AdministrationLifecycle\DependencyChecks\GuardianLifecycleDependencyCheck;
use App\Services\AdministrationLifecycle\DependencyChecks\RoleLifecycleDependencyCheck;
use App\Services\AdministrationLifecycle\DependencyChecks\SchoolLifecycleDependencyCheck;
use App\Services\AdministrationLifecycle\DependencyChecks\UserLifecycleDependencyCheck;
use App\Services\Concerns\AuthorizesAdministrationLifecycle;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class AdministrationLifecycleService
{
    use AuthorizesAdministrationLifecycle;

    public function __construct(
        private readonly AdministrationResourceRegistry $registry,
        private readonly TenantContextService $tenantContext,
        private readonly LifecycleTransitionRules $rules,
        private readonly LifecycleHistoryRecorder $history,
        private readonly SchoolLifecycleDependencyCheck $schoolDependencies,
        private readonly UserLifecycleDependencyCheck $userDependencies,
        private readonly RoleLifecycleDependencyCheck $roleDependencies,
        private readonly AcademicYearLifecycleDependencyCheck $academicYearDependencies,
        private readonly AcademicPeriodLifecycleDependencyCheck $academicPeriodDependencies,
        private readonly GuardianLifecycleDependencyCheck $guardianDependencies,
    ) {}

    public function apply(User $actor, ?TenantContext $context, string $resourceType, string $uuid, string $action, ApplyLifecycleTransitionData $data): AdministrationLifecycleResult
    {
        $config = $this->registry->config($resourceType);
        $query = $config['model']::query()->with($config['relations'])->withTrashed();

        if ($config['scope'] === 'school') {
            $school = $this->tenantContext->requireSchool($context);
            $resource = $query->where('school_id', $school->id)->where('uuid', $uuid)->firstOrFail();
            $this->assertSchoolLifecyclePermission($actor, $school, "{$config['permission']}.lifecycle");
        } else {
            $this->assertPlatformLifecyclePermission($actor, 'schools.lifecycle');
            $resource = $query->where('uuid', $uuid)->firstOrFail();
        }

        return $this->applyToResource($actor, $resource, $action, $data);
    }

    public function applyToResource(User $actor, Model $resource, string $action, ApplyLifecycleTransitionData $data): AdministrationLifecycleResult
    {
        $this->rules->assertTransitionAllowed($resource, $action);
        $this->assertNoDependencies($resource, $action);
        $fromStatus = $resource->getAttribute('status');
        $toStatus = $this->rules->statusAfter($resource, $action);

        return DB::transaction(function () use ($actor, $resource, $action, $data, $fromStatus, $toStatus): AdministrationLifecycleResult {
            if ($action === LifecycleAction::ACTIVATE || $action === LifecycleAction::DEACTIVATE) {
                $resource->forceFill(['status' => $toStatus])->save();
            }

            if ($action === LifecycleAction::DELETE) {
                $resource->delete();
            }

            if ($action === LifecycleAction::RESTORE) {
                $resource->restore();
            }

            if ($action !== LifecycleAction::DELETE) {
                $resource->refresh();
            }

            $history = $this->history->record(
                $resource,
                $actor,
                LifecycleAction::operationForAction($action),
                $data->effectiveAt,
                $data->reason,
                $fromStatus === null ? null : (string) $fromStatus,
                $toStatus,
            );

            return new AdministrationLifecycleResult(
                resource_type: $this->registry->resourceTypeForModel($resource),
                resource_uuid: (string) $resource->getAttribute('uuid'),
                action: $action,
                status: (string) ($resource->getAttribute('status') ?? $toStatus),
                history: $history,
            );
        });
    }

    private function assertNoDependencies(Model $resource, string $action): void
    {
        foreach ([
            $this->schoolDependencies,
            $this->userDependencies,
            $this->roleDependencies,
            $this->academicYearDependencies,
            $this->academicPeriodDependencies,
            $this->guardianDependencies,
        ] as $checker) {
            $checker->assertNoConflicts($resource, $action);
        }
    }
}
