<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Exceptions\ConflictException;
use App\Models\School;
use App\Models\SupportAccessDecision;
use App\Models\TargetSchoolSupportOptIn;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class SupportAccessDecisionService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function request(User $actor, array $data): SupportAccessDecision
    {
        $this->authorization->authorizeSupportAccessRequest($actor);
        $school = $this->resolveSchool($data['school_id']);
        $optIn = $this->resolveActiveOptIn($actor, $school, $data['support_opt_in_id'], $data);

        return DB::transaction(function () use ($actor, $data, $school, $optIn): SupportAccessDecision {
            $decision = SupportAccessDecision::query()->create([
                'actor_user_id' => $actor->id,
                'school_id' => $school->id,
                'target_school_support_opt_in_id' => $optIn->id,
                'reason_code' => $data['reason_code'],
                'purpose' => $data['purpose'],
                'correlation_id' => $data['correlation_id'],
                'state' => 'requested',
                'support_opt_in_state' => 'approved',
                'platform_approval_state' => 'pending',
            ]);

            $this->audit->record($actor, 'support_access_requested', 'pending', $decision->reason_code, $decision->correlation_id, $school, $decision, $optIn);

            return $decision->load(['actor', 'school']);
        });
    }

    public function get(User $actor, string $decisionUuid): SupportAccessDecision
    {
        $this->authorization->authorizeSupportAccessRequest($actor);

        $decision = SupportAccessDecision::query()
            ->where('uuid', $decisionUuid)
            ->where('actor_user_id', $actor->id)
            ->with(['actor', 'school'])
            ->first();

        if ($decision === null) {
            throw (new ModelNotFoundException)->setModel(SupportAccessDecision::class);
        }

        return $decision;
    }

    public function authorizeDiagnostics(User $actor, string $schoolUuid, string $decisionUuid): SupportAccessDecision
    {
        $this->authorization->authorizeSupportAccessRequest($actor);
        $school = $this->resolveSchool($schoolUuid);

        $decision = SupportAccessDecision::query()
            ->where('uuid', $decisionUuid)
            ->where('actor_user_id', $actor->id)
            ->where('school_id', $school->id)
            ->with(['actor', 'school', 'targetSchoolSupportOptIn', 'internalPlatformApproval'])
            ->first();

        if ($decision === null) {
            $this->auditHiddenDecisionMismatch($actor, $decisionUuid);

            throw (new ModelNotFoundException)->setModel(SupportAccessDecision::class);
        }

        $this->assertApprovedGate($actor, $decision);

        return $decision;
    }

    private function assertApprovedGate(User $actor, SupportAccessDecision $decision): void
    {
        $optIn = $decision->targetSchoolSupportOptIn;
        $approval = $decision->internalPlatformApproval;

        if ($decision->state !== 'approved' || $decision->expires_at === null || $decision->expires_at->isPast()) {
            $this->auditDeniedGate($actor, $decision, 'support_access_inactive');
            throw new ConflictException('Support access is not active.');
        }

        if ($optIn === null || $optIn->state !== 'approved' || $optIn->expires_at === null || $optIn->expires_at->isPast()) {
            $this->auditDeniedGate($actor, $decision, 'support_opt_in_inactive');
            throw new ConflictException('Target-school support opt-in is not active.');
        }

        if ($approval === null || $approval->state !== 'approved' || $approval->expires_at === null || $approval->expires_at->isPast()) {
            $this->auditDeniedGate($actor, $decision, 'platform_approval_inactive');
            throw new ConflictException('Internal platform approval is not active.');
        }

        if ($approval->school_id !== $decision->school_id || $approval->support_actor_user_id !== $decision->actor_user_id || $optIn->school_id !== $decision->school_id) {
            $this->auditDeniedGate($actor, $decision, 'support_gate_mismatch');
            throw new ConflictException('Support access gates do not match the target school and actor.');
        }
    }

    private function auditDeniedGate(User $actor, SupportAccessDecision $decision, string $reasonCode): void
    {
        $this->audit->record(
            actor: $actor,
            action: 'conflict_detected',
            outcome: 'conflicted',
            reasonCode: $reasonCode,
            correlationId: $decision->correlation_id,
            school: $decision->school,
            supportAccessDecision: $decision,
            metadata: ['gate' => $reasonCode],
        );
    }

    private function auditHiddenDecisionMismatch(User $actor, string $decisionUuid): void
    {
        if (! SupportAccessDecision::query()->where('uuid', $decisionUuid)->exists()) {
            return;
        }

        $this->audit->record(
            actor: $actor,
            action: 'denied_access',
            outcome: 'denied',
            reasonCode: 'support_access_mismatched_context',
            correlationId: 'support-access-mismatch',
            metadata: ['decision_lookup' => 'mismatched_or_unauthorized'],
        );
    }

    private function resolveSchool(string $schoolUuid): School
    {
        $school = School::query()->where('uuid', $schoolUuid)->first();

        if ($school === null) {
            throw (new ModelNotFoundException)->setModel(School::class);
        }

        return $school;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveActiveOptIn(User $actor, School $school, string $optInUuid, array $data): TargetSchoolSupportOptIn
    {
        $optIn = TargetSchoolSupportOptIn::query()
            ->where('uuid', $optInUuid)
            ->where('school_id', $school->id)
            ->first();

        if ($optIn === null) {
            throw (new ModelNotFoundException)->setModel(TargetSchoolSupportOptIn::class);
        }

        if ($optIn->state !== 'approved' || $optIn->expires_at === null || $optIn->expires_at->isPast()) {
            $this->audit->record(
                actor: $actor,
                action: 'conflict_detected',
                outcome: 'conflicted',
                reasonCode: 'support_opt_in_inactive',
                correlationId: (string) ($data['correlation_id'] ?? $optIn->correlation_id),
                school: $school,
                targetSchoolSupportOptIn: $optIn,
                metadata: ['gate' => 'target_school_support_opt_in'],
            );

            throw new ConflictException('Support access requires an active target-school opt-in.');
        }

        return $optIn;
    }
}
