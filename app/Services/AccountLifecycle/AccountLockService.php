<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AccountLifecycle\AccountLockData;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TenantContextException;
use App\Models\AccountLock;
use App\Models\School;
use App\Models\User;
use App\Policies\AccountLifecyclePolicy;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class AccountLockService
{
    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly AccountLifecyclePolicy $policy,
        private readonly BearerTokenRevocationService $bearerTokens,
        private readonly AccountLifecycleAuditService $audit,
    ) {}

    public function show(User $actor, TenantContext $context, string $userId): array|AccountLock
    {
        $target = $this->target($actor, $context, $userId);
        $lock = $this->repository->activeAdministrativeLock($target);

        if ($lock === null) {
            return [
                'user_id' => $target->uuid,
                'school_id' => $target->school?->uuid,
                'status' => 'none',
            ];
        }

        return $lock->load(['user', 'school']);
    }

    public function lock(User $actor, TenantContext $context, AccountLockData $data, ?string $sourceIp = null): AccountLock
    {
        $target = $this->target($actor, $context, $data->userId);

        if ($this->repository->activeAdministrativeLock($target) !== null) {
            throw new ConflictException('Account is already administratively locked.');
        }

        return DB::transaction(function () use ($actor, $target, $data, $sourceIp): AccountLock {
            $lock = AccountLock::query()->create([
                'user_id' => $target->id,
                'school_id' => $target->school_id,
                'actor_user_id' => $actor->id,
                'lock_type' => 'administrative',
                'status' => 'active',
                'reason' => $data->reason,
                'locked_at' => now(),
            ]);

            $this->bearerTokens->revokeAllForUser($target);
            $this->audit->record('account_locked', 'success', $target, $actor, $sourceIp);

            return $lock->load(['user', 'school']);
        });
    }

    public function unlock(User $actor, TenantContext $context, string $userId, ?string $sourceIp = null): array
    {
        $target = $this->target($actor, $context, $userId);
        $lock = $this->repository->activeAdministrativeLock($target);

        if ($lock === null) {
            throw new ConflictException('Account is not administratively locked.');
        }

        DB::transaction(function () use ($actor, $target, $lock, $sourceIp): void {
            $lock->forceFill([
                'status' => 'cleared',
                'cleared_at' => now(),
            ])->save();

            $this->bearerTokens->revokeAllForUser($target);
            $this->audit->record('account_unlocked', 'success', $target, $actor, $sourceIp);
        });

        return [
            'user_id' => $target->uuid,
            'school_id' => $target->school?->uuid,
            'status' => $target->status,
            'action' => 'account_unlocked',
        ];
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
}
