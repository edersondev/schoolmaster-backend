<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AuditEventData;
use App\DTOs\School\SchoolProfileData;
use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

final class SchoolService
{
    public function __construct(
        private readonly AuditEventService $audit,
        private readonly SchoolProfileService $profiles,
    ) {}

    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $this->assertPlatformPermission($actor, 'schools.view');

        return School::query()
            ->with('address')
            ->when(array_key_exists('status', $filters), fn ($query) => $query->where('status', (int) $filters['status']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function create(User $actor, array $data, ?string $sourceIp = null, ?UploadedFile $logoFile = null): School
    {
        $this->assertPlatformPermission($actor, 'schools.manage');

        $school = $this->profiles->create(SchoolProfileData::fromArray($data, $logoFile));

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

    public function update(User $actor, string $schoolUuid, array $data, ?string $sourceIp = null, ?UploadedFile $logoFile = null): School
    {
        $this->assertPlatformPermission($actor, 'schools.manage');

        /** @var School $school */
        $school = School::query()->where('uuid', $schoolUuid)->firstOrFail();
        $oldStatus = $school->status;
        $school = $this->profiles->update($school, SchoolProfileData::fromArray($data, $logoFile));

        $eventType = 'school_updated';
        if (isset($data['status']) && (int) $data['status'] !== (int) $oldStatus) {
            $eventType = (int) $data['status'] === 1 ? 'school_activated' : 'school_deactivated';
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

        return $school;
    }

    private function assertPlatformPermission(User $actor, string $permission): void
    {
        if (! $actor->hasPermission($permission, 'platform')) {
            throw new AuthorizationException('The authenticated user lacks permission for this action.');
        }
    }
}
