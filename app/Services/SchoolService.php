<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AuditEventData;
use App\Models\School;
use App\Models\User;
use App\Services\Addresses\SchoolAddressService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SchoolService
{
    public function __construct(
        private readonly AuditEventService $audit,
        private readonly SchoolAddressService $addresses,
    ) {}

    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $this->assertPlatformPermission($actor, 'schools.view');

        return School::query()
            ->with('address')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function create(User $actor, array $data, ?string $sourceIp = null): School
    {
        $this->assertPlatformPermission($actor, 'schools.manage');

        $addressPayload = $data['address'] ?? null;
        unset($data['address']);

        $school = School::query()->create($data);

        if ($addressPayload !== null) {
            $this->addresses->applySubmittedAddress($school, ['address' => $addressPayload]);
            $school->load('address');
        }

        $this->audit->record(new AuditEventData(
            eventType: 'school_created',
            outcome: 'success',
            actorUserId: $actor->id,
            schoolId: $school->id,
            affectedResourceType: School::class,
            affectedResourceId: $school->uuid,
            sourceIp: $sourceIp,
        ));

        return $school;
    }

    public function get(User $actor, string $schoolUuid): School
    {
        $this->assertPlatformPermission($actor, 'schools.view');

        return School::query()->with('address')->where('uuid', $schoolUuid)->firstOrFail();
    }

    public function update(User $actor, string $schoolUuid, array $data, ?string $sourceIp = null): School
    {
        $this->assertPlatformPermission($actor, 'schools.manage');

        /** @var School $school */
        $school = School::query()->where('uuid', $schoolUuid)->firstOrFail();
        $oldStatus = $school->status;
        $this->addresses->applySubmittedAddress($school, $data);
        unset($data['address']);

        $school->fill($data);
        $school->save();

        $eventType = 'school_updated';
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $eventType = $data['status'] === 'active' ? 'school_activated' : 'school_deactivated';
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

        return $school->refresh()->load('address');
    }

    private function assertPlatformPermission(User $actor, string $permission): void
    {
        if (! $actor->hasPermission($permission, 'platform')) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }
}
