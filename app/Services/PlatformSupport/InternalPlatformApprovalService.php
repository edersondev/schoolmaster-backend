<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

use App\Exceptions\ConflictException;
use App\Models\InternalPlatformApproval;
use App\Models\SupportAccessDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class InternalPlatformApprovalService
{
    public function __construct(
        private PlatformSupportAuthorizationService $authorization,
        private PlatformSupportAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(User $actor, string $decisionUuid, array $data): SupportAccessDecision
    {
        return DB::transaction(function () use ($actor, $decisionUuid, $data): SupportAccessDecision {
            $this->authorization->authorizeSupportApproval($actor);
            $decision = $this->resolveDecision($decisionUuid);
            $optIn = $decision->targetSchoolSupportOptIn;

            if ($optIn === null || $optIn->state !== 'approved' || $optIn->expires_at === null || $optIn->expires_at->isPast()) {
                throw new ConflictException('Support access requires an active target-school opt-in.');
            }

            if ($decision->state === 'revoked') {
                throw new ConflictException('Revoked support access cannot be approved.');
            }

            $approval = InternalPlatformApproval::query()->create([
                'support_access_decision_id' => $decision->id,
                'approver_user_id' => $actor->id,
                'support_actor_user_id' => $decision->actor_user_id,
                'school_id' => $decision->school_id,
                'state' => 'approved',
                'reason_code' => $data['reason_code'],
                'correlation_id' => $data['correlation_id'],
                'approved_at' => now(),
                'expires_at' => now()->addDay(),
            ]);

            $expiresAt = $approval->expires_at->lessThan($optIn->expires_at)
                ? $approval->expires_at
                : $optIn->expires_at;

            $decision->update([
                'internal_platform_approval_id' => $approval->id,
                'state' => 'approved',
                'platform_approval_state' => 'approved',
                'support_opt_in_state' => 'approved',
                'approved_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            $this->audit->record($actor, 'support_access_approved', 'allowed', $data['reason_code'], $data['correlation_id'], $decision->school, $decision, internalPlatformApproval: $approval);

            return $decision->refresh()->load(['actor', 'school']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function revoke(User $actor, string $decisionUuid, array $data): SupportAccessDecision
    {
        return DB::transaction(function () use ($actor, $decisionUuid, $data): SupportAccessDecision {
            $this->authorization->authorizeSupportApproval($actor);
            $decision = $this->resolveDecision($decisionUuid);

            $decision->internalPlatformApproval?->update([
                'state' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason_code' => $data['reason_code'],
            ]);

            $decision->update([
                'state' => 'revoked',
                'platform_approval_state' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason_code' => $data['reason_code'],
            ]);

            $this->audit->record($actor, 'support_access_revoked', 'revoked', $data['reason_code'], $data['correlation_id'], $decision->school, $decision, internalPlatformApproval: $decision->internalPlatformApproval);

            return $decision->refresh()->load(['actor', 'school']);
        });
    }

    private function resolveDecision(string $decisionUuid): SupportAccessDecision
    {
        $decision = SupportAccessDecision::query()
            ->where('uuid', $decisionUuid)
            ->with(['actor', 'school', 'targetSchoolSupportOptIn', 'internalPlatformApproval'])
            ->lockForUpdate()
            ->first();

        if ($decision === null) {
            throw (new ModelNotFoundException)->setModel(SupportAccessDecision::class);
        }

        return $decision;
    }
}
