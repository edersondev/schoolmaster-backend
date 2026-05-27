<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AccountLifecycle\AccountRecoveryData;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TenantContextException;
use App\Models\AccountRecovery;
use App\Models\School;
use App\Models\User;
use App\Policies\AccountLifecyclePolicy;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class AccountRecoveryService
{
    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly AccountLifecyclePolicy $policy,
        private readonly BearerTokenRevocationService $bearerTokens,
        private readonly AccountLifecycleAuditService $audit,
    ) {}

    public function recover(User $actor, TenantContext $context, AccountRecoveryData $data, ?string $sourceIp = null): array
    {
        $target = $this->target($actor, $context, $data->userId);

        return match ($data->action) {
            'unlock' => $this->unlock($actor, $target, $data->reason, $sourceIp),
            'reactivate' => $this->reactivate($actor, $target, $data->reason, $sourceIp),
            default => throw new ConflictException('Unsupported account recovery action.'),
        };
    }

    private function unlock(User $actor, User $target, ?string $reason, ?string $sourceIp): array
    {
        $lock = $this->repository->activeAdministrativeLock($target);

        if ($lock === null) {
            throw new ConflictException('Account is not administratively locked.');
        }

        DB::transaction(function () use ($actor, $target, $lock, $reason, $sourceIp): void {
            $lock->forceFill([
                'status' => 'cleared',
                'cleared_at' => now(),
            ])->save();

            AccountRecovery::query()->create([
                'user_id' => $target->id,
                'school_id' => $target->school_id,
                'account_lock_id' => $lock->id,
                'actor_user_id' => $actor->id,
                'recovery_type' => 'unlock',
                'from_state' => 'locked',
                'to_state' => $target->status,
                'reason' => $reason,
            ]);

            $this->bearerTokens->revokeAllForUser($target);
            $this->audit->record('account_recovered', 'success', $target, $actor, $sourceIp, ['action' => 'unlock']);
        });

        return $this->result($target, 'account_unlocked');
    }

    private function reactivate(User $actor, User $target, ?string $reason, ?string $sourceIp): array
    {
        if ($target->status !== 'inactive') {
            throw new ConflictException('Only inactive accounts can be reactivated.');
        }

        if ($target->school !== null && $target->school->status !== 'active') {
            throw new ConflictException('Inactive schools cannot reactivate account access.');
        }

        if ($target->roles()->where('roles.status', 'active')->count() === 0) {
            throw new ConflictException('Account requires at least one active role before reactivation.');
        }

        if ($this->repository->activeAdministrativeLock($target) !== null) {
            throw new ConflictException('Locked accounts must be unlocked before reactivation.');
        }

        DB::transaction(function () use ($actor, $target, $reason, $sourceIp): void {
            $target->forceFill(['status' => 'active'])->save();
            AccountRecovery::query()->create([
                'user_id' => $target->id,
                'school_id' => $target->school_id,
                'actor_user_id' => $actor->id,
                'recovery_type' => 'reactivate',
                'from_state' => 'inactive',
                'to_state' => 'active',
                'reason' => $reason,
            ]);

            $this->bearerTokens->revokeAllForUser($target);
            $this->audit->record('account_reactivated', 'success', $target, $actor, $sourceIp);
        });

        return $this->result($target->refresh()->load('school'), 'account_reactivated');
    }

    private function target(User $actor, TenantContext $context, string $userId): User
    {
        $target = $this->repository->findUserByUuid($userId);

        if (! $target instanceof User) {
            throw new ModelNotFoundException();
        }

        $school = $target->school_id === null ? null : $context->school;
        if ($target->school_id !== null && ($school === null || $school->id !== $target->school_id)) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        $scope = $target->school_id === null ? 'platform' : 'school';
        $policySchool = $scope === 'school' ? $school : null;

        if (! $this->policy->manage($actor, $scope, $policySchool instanceof School ? $policySchool : null)) {
            throw new PermissionDeniedException('The authenticated user lacks permission for this action.');
        }

        return $target;
    }

    private function result(User $target, string $action): array
    {
        return [
            'user_id' => $target->uuid,
            'school_id' => $target->school?->uuid,
            'status' => $target->status,
            'action' => $action,
        ];
    }
}
