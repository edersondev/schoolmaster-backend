<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

use App\DTOs\AdministrationLifecycle\UpdateAdministrationResourceData;
use App\DTOs\AuditEventData;
use App\DTOs\TenantContext;
use App\Models\AcademicPeriod;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\AuditEventService;
use App\Services\Addresses\SchoolAddressService;
use App\Services\Concerns\AuthorizesAdministrationLifecycle;
use App\Services\Roles\RoleService;
use App\Services\TenantContextService;
use App\Services\Users\UserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdministrationUpdateService
{
    use AuthorizesAdministrationLifecycle;

    public function __construct(
        private readonly AdministrationResourceRegistry $registry,
        private readonly TenantContextService $tenantContext,
        private readonly AdministrationLifecycleService $lifecycle,
        private readonly LifecycleHistoryRecorder $history,
        private readonly UserService $users,
        private readonly RoleService $roles,
        private readonly AuditEventService $audit,
        private readonly SchoolAddressService $addresses,
    ) {}

    public function update(User $actor, ?TenantContext $context, string $resourceType, string $uuid, UpdateAdministrationResourceData $data, ?string $sourceIp = null): Model
    {
        $config = $this->registry->config($resourceType);
        $resource = app(AdministrationDetailService::class)->get($actor, $context, $resourceType, $uuid);

        if ($config['scope'] === 'school') {
            $school = $this->tenantContext->requireSchool($context);
            $this->assertSchoolLifecyclePermission($actor, $school, "{$config['permission']}.manage");
        } else {
            $this->assertPlatformLifecyclePermission($actor, 'schools.manage');
        }

        $attributes = array_intersect_key($data->attributes, array_flip($config['mutable']));
        $addressWasSubmitted = array_key_exists('address', $attributes);
        $addressPayload = $attributes['address'] ?? null;
        unset($attributes['address']);
        $fromStatus = (string) ($resource->getAttribute('status') ?? '');

        return DB::transaction(function () use ($resource, $resourceType, $attributes, $data, $actor, $fromStatus, $sourceIp, $addressWasSubmitted, $addressPayload): Model {
            if ($resourceType === 'users' && array_key_exists('role_ids', $data->attributes)) {
                /** @var User $resource */
                $roles = $this->users->activeSchoolRoles($data->attributes['role_ids'], (int) $resource->school_id);
                $resource->roles()->sync($roles->pluck('id')->all());
            }

            if ($resourceType === 'roles' && array_key_exists('permission_ids', $data->attributes)) {
                /** @var Role $resource */
                $permissions = $this->roles->activePermissions($data->attributes['permission_ids'], (string) $resource->scope);
                $resource->permissions()->sync($permissions->pluck('id')->all());
            }

            if ($resource instanceof AcademicPeriod && isset($attributes['sequence'])) {
                $duplicate = AcademicPeriod::query()
                    ->where('academic_year_id', $resource->academic_year_id)
                    ->where('sequence', $attributes['sequence'])
                    ->whereKeyNot($resource->id)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages(['sequence' => ['The sequence must be unique within the academic year.']]);
                }
            }

            $this->assertStatusTransitionAllowed($resource, $attributes);

            $resource->fill($attributes);
            $resource->save();

            if ($resource instanceof School && $addressWasSubmitted) {
                $this->addresses->applySubmittedAddress($resource, ['address' => $addressPayload]);
            }

            $this->history->record(
                $resource,
                $actor,
                LifecycleAction::UPDATED,
                now()->toDateString(),
                'Administrative update',
                $fromStatus,
                (string) ($resource->getAttribute('status') ?? ''),
                ['updated_fields' => array_keys($attributes)],
            );

            if ($resource instanceof School) {
                $this->recordSchoolUpdateAudit($resource, $actor, $attributes, $fromStatus, $sourceIp);
            }

            return $resource->refresh()->load($this->registry->config($resourceType)['relations']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertStatusTransitionAllowed(Model $resource, array $attributes): void
    {
        if (! array_key_exists('status', $attributes)) {
            return;
        }

        $fromStatus = (string) ($resource->getAttribute('status') ?? '');
        $toStatus = (string) $attributes['status'];

        if ($fromStatus === $toStatus) {
            return;
        }

        $action = match ($toStatus) {
            'active' => LifecycleAction::ACTIVATE,
            'inactive' => LifecycleAction::DEACTIVATE,
            default => null,
        };

        if ($action === null) {
            return;
        }

        $this->lifecycle->assertTransitionEligibility($resource, $action);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordSchoolUpdateAudit(School $school, User $actor, array $attributes, string $fromStatus, ?string $sourceIp): void
    {
        $eventType = 'school_updated';
        if (array_key_exists('status', $attributes) && (string) $attributes['status'] !== $fromStatus) {
            $eventType = $attributes['status'] === 'active' ? 'school_activated' : 'school_deactivated';
        }

        $this->audit->record(new AuditEventData(
            eventType: $eventType,
            outcome: 'success',
            actorUserId: $actor->id,
            schoolId: $school->id,
            affectedResourceType: School::class,
            affectedResourceId: $school->uuid,
            sourceIp: $sourceIp,
        ));
    }
}
