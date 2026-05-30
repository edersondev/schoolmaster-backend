<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\AuditEventData;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use App\Services\AuditEventService;

final readonly class RosterAuditLogger
{
    public function __construct(private AuditEventService $auditEvents) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $outcome,
        School $school,
        ?User $actor = null,
        ?string $targetType = null,
        ?string $targetUuid = null,
        ?string $reason = null,
        array $metadata = [],
        ?string $sourceIp = null,
    ): AuditEvent {
        $summary = $this->tenantSafeMetadata($metadata);

        if ($reason !== null) {
            $summary['lifecycle_reason'] = $reason;
        }

        return $this->auditEvents->record(new AuditEventData(
            eventType: 'classroom_roster.'.$action,
            outcome: $outcome,
            actorUserId: $actor?->id,
            schoolId: $school->id,
            affectedResourceType: $targetType,
            affectedResourceId: $targetUuid,
            sourceIp: $sourceIp,
            metadata: $summary,
        ));
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function tenantSafeMetadata(array $metadata): array
    {
        $allowed = [
            'academic_period_id',
            'batch_size',
            'code',
            'conflict_type',
            'status',
            'target_status',
        ];

        return array_intersect_key($metadata, array_flip($allowed));
    }
}
