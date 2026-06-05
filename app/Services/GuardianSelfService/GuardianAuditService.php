<?php

declare(strict_types=1);

namespace App\Services\GuardianSelfService;

use App\DTOs\AuditEventData;
use App\DTOs\GuardianSelfService\GuardianActorContext;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AuditEventService;
use Illuminate\Http\Request;

final class GuardianAuditService
{
    public function __construct(private readonly AuditEventService $audit) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function allowed(Request $request, GuardianActorContext $actor, string $action, ?StudentProfile $student = null, array $metadata = []): void
    {
        $this->audit->record(new AuditEventData(
            eventType: 'guardian_self_service.'.$action,
            actorUserId: $actor->user->id,
            schoolId: $actor->school->id,
            affectedResourceType: $student === null ? 'guardian' : 'student_profile',
            affectedResourceId: $student?->uuid ?? $actor->guardian->uuid,
            outcome: 'allowed',
            sourceIp: $request->ip(),
            metadata: $metadata + ['guardian_id' => $actor->guardian->uuid],
        ));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function denied(Request $request, string $action, string $reason, ?User $actor = null, ?School $school = null, ?string $targetId = null, array $metadata = []): void
    {
        $this->audit->record(new AuditEventData(
            eventType: 'guardian_self_service.'.$action,
            actorUserId: $actor?->id,
            schoolId: $school?->id,
            affectedResourceType: $targetId === null ? null : 'student_profile',
            affectedResourceId: $targetId,
            outcome: 'denied',
            sourceIp: $request->ip(),
            metadata: $metadata + ['reason' => $reason],
        ));
    }
}
