<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\DTOs\AuditEventData;
use App\Models\AuditEvent;
use App\Models\School;
use App\Models\User;
use App\Services\AuditEventService;

final readonly class DuplicateEmailAuditService
{
    public function __construct(private AuditEventService $audit) {}

    public function record(
        User $actor,
        ?School $school,
        string $scope,
        string $workflow,
        string $outcome,
        string $canonicalEmail,
        string $reasonCode,
        ?string $sourceIp = null,
        ?User $target = null,
    ): AuditEvent {
        $mayRecordTarget = $outcome === 'recoverable_user_conflict' && $target !== null;

        return $this->audit->record(new AuditEventData(
            eventType: 'user_creation_duplicate_email',
            outcome: $outcome,
            actorUserId: $actor->id,
            schoolId: $school?->id,
            affectedResourceType: $mayRecordTarget ? 'user' : null,
            affectedResourceId: $mayRecordTarget ? $target->uuid : null,
            sourceIp: $sourceIp,
            metadata: [
                'scope' => $scope,
                'workflow' => $workflow,
                'email_hash' => hash('sha256', $canonicalEmail),
                'reason_code' => $reasonCode,
            ],
        ));
    }
}
