<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Models\InternalPlatformApproval;
use App\Models\PlatformSupportAuditEvent;
use App\Models\School;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Models\User;

final readonly class PlatformSupportAuditService
{
    public function __construct(
        private PlatformSupportRedactionService $redaction,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        ?User $actor,
        string $action,
        string $outcome,
        string $reasonCode,
        string $correlationId,
        ?School $school = null,
        ?SupportAccessDecision $supportAccessDecision = null,
        ?TargetSchoolSupportOptIn $targetSchoolSupportOptIn = null,
        ?InternalPlatformApproval $internalPlatformApproval = null,
        ?string $targetType = null,
        ?string $targetId = null,
        array $metadata = [],
    ): PlatformSupportAuditEvent {
        return PlatformSupportAuditEvent::query()->create([
            'actor_user_id' => $actor?->id,
            'school_id' => $school?->id,
            'support_access_decision_id' => $supportAccessDecision?->id,
            'target_school_support_opt_in_id' => $targetSchoolSupportOptIn?->id,
            'internal_platform_approval_id' => $internalPlatformApproval?->id,
            'action' => $action,
            'outcome' => $outcome,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'correlation_id' => $correlationId,
            'reason_code' => $reasonCode,
            'metadata' => $this->redaction->auditMetadata($metadata),
            'occurred_at' => now(),
        ]);
    }
}
