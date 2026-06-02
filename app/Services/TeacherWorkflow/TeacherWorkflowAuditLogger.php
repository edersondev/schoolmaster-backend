<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflow;

use App\DTOs\AuditEventData;
use App\Models\AuditEvent;
use App\Services\AuditEventService;

final class TeacherWorkflowAuditLogger
{
    public function __construct(
        private readonly AuditEventService $auditEventService,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $eventType,
        string $outcome,
        ?int $actorUserId = null,
        ?int $schoolId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        array $metadata = [],
    ): AuditEvent {
        return $this->auditEventService->record(new AuditEventData(
            eventType: $eventType,
            outcome: $outcome,
            actorUserId: $actorUserId,
            schoolId: $schoolId,
            affectedResourceType: $targetType,
            affectedResourceId: $targetId,
            metadata: $this->tenantSafeMetadata($metadata),
        ));
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function tenantSafeMetadata(array $metadata): array
    {
        unset(
            $metadata['file_contents'],
            $metadata['storage_path'],
            $metadata['credentials'],
            $metadata['request_payload'],
            $metadata['cross_tenant_details'],
        );

        return $metadata;
    }
}
