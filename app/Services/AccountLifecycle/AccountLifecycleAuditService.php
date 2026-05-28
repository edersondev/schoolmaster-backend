<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AuditEventData;
use App\Models\User;
use App\Services\AuditEventService;

final class AccountLifecycleAuditService
{
    public function __construct(private readonly AuditEventService $audit) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(string $eventType, string $outcome, ?User $target = null, ?User $actor = null, ?string $sourceIp = null, array $metadata = []): void
    {
        $this->audit->record(new AuditEventData(
            eventType: $eventType,
            outcome: $outcome,
            actorUserId: $actor?->id,
            schoolId: $target?->school_id,
            affectedResourceType: $target === null ? null : 'user',
            affectedResourceId: $target?->uuid,
            sourceIp: $sourceIp,
            metadata: $metadata,
        ));
    }
}
