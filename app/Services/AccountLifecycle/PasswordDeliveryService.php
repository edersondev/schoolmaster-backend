<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Exceptions\PasswordDeliveryException;
use App\Exceptions\PasswordDeliveryRateLimitException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TenantContextException;
use App\Models\PasswordResetRequest;
use App\Models\School;
use App\Models\User;
use App\Policies\AccountLifecyclePolicy;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class PasswordDeliveryService
{
    private const DELIVERY_LIMIT = 3;

    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly AccountLifecyclePolicy $policy,
        private readonly LifecycleTokenService $tokens,
        private readonly PasswordResetService $passwordResets,
        private readonly AccountLifecycleAuditService $audit,
        private readonly EmailDeliveryRequestMetadataService $deliveryMetadata,
        private readonly PasswordDeliveryMailService $mail,
    ) {}

    public function request(
        User $actor,
        TenantContext $context,
        string $userId,
        bool $tenantHeaderPresent,
        ?string $sourceIp = null,
    ): PasswordResetRequest {
        $school = $context->school;
        $scope = $school === null ? 'platform' : 'school';

        if ($school !== null && ! $tenantHeaderPresent) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        $this->authorize($actor, $scope, $school);
        $target = $school === null
            ? $this->repository->findPlatformUserByUuidIncludingDeleted($userId)
            : $this->repository->findSchoolUserByUuidIncludingDeleted($userId, $school->id);

        if (! $target instanceof User) {
            throw new ModelNotFoundException;
        }

        $this->authorize($actor, $scope, $school, $target);
        $this->assertEligible($target);

        try {
            [$delivery, $purpose] = DB::transaction(function () use ($target, $sourceIp): array {
                $lockedTarget = $this->repository->lockUserForPasswordDelivery($target);
                $this->assertEligible($lockedTarget);
                $recentDeliveries = $this->repository->acceptedPasswordDeliveryCount($lockedTarget);

                if (
                    $recentDeliveries >= self::DELIVERY_LIMIT
                    || $this->passwordResets->isIssuanceSuppressed($lockedTarget, $sourceIp)
                ) {
                    throw new PasswordDeliveryRateLimitException(
                        'Password delivery is temporarily limited. Try again later.',
                    );
                }

                $purpose = $this->repository->hasCompletedPasswordReset($lockedTarget)
                    ? 'password_reset'
                    : 'password_setup';
                [$candidate, $plainToken, $metadata] = $this->candidate(
                    $lockedTarget,
                    $purpose,
                    $sourceIp,
                    $recentDeliveries + 1,
                );

                try {
                    $this->mail->send($lockedTarget, $plainToken, $candidate->expires_at, $purpose);
                } finally {
                    unset($plainToken);
                }

                PasswordResetRequest::query()
                    ->where('target_user_id', $lockedTarget->id)
                    ->where('school_id', $lockedTarget->school_id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $candidate->getKey())
                    ->update([
                        'status' => 'superseded',
                        'superseded_at' => now(),
                    ]);

                $candidate->forceFill([
                    'status' => 'pending',
                    'superseded_at' => null,
                    'delivery_requested_at' => now(),
                    'delivery_channel' => 'email',
                    'email_delivery_metadata_summary' => $metadata,
                ])->save();

                return [$candidate->refresh(), $purpose];
            }, 3);
        } catch (PasswordDeliveryRateLimitException $exception) {
            $this->audit->recordPasswordDelivery('password_delivery_limited', 'failure', $target, $actor, $sourceIp);
            throw $exception;
        } catch (PasswordDeliveryException $exception) {
            $this->audit->recordPasswordDelivery('password_delivery_failed', 'failure', $target, $actor, $sourceIp);
            throw $exception;
        }

        $this->audit->recordPasswordDelivery(
            'password_delivery_requested',
            'success',
            $target,
            $actor,
            $sourceIp,
            $purpose,
        );

        return $delivery;
    }

    private function authorize(User $actor, string $scope, ?School $school, ?User $target = null): void
    {
        if (! $this->policy->deliverPassword($actor, $scope, $school, $target)) {
            throw new PermissionDeniedException('The authenticated user lacks permission for this action.');
        }
    }

    private function assertEligible(User $target): void
    {
        if ($target->trashed() || $target->status !== 'active') {
            throw new ConflictException('Account is not eligible for password delivery.');
        }

        if ($target->school !== null && ! $target->school->isActive()) {
            throw new ConflictException('Account is not eligible for password delivery.');
        }

        if ($this->repository->activeAdministrativeLock($target) !== null) {
            throw new ConflictException('Locked accounts cannot receive password delivery.');
        }
    }

    /**
     * @return array{PasswordResetRequest, string, array<string, mixed>}
     */
    private function candidate(
        User $target,
        string $purpose,
        ?string $sourceIp,
        int $requestCount,
    ): array {
        [$plainToken, $tokenHash] = $this->tokens->issue();
        $candidate = PasswordResetRequest::query()->create([
            'target_user_id' => $target->id,
            'school_id' => $target->school_id,
            'account_identifier_hash' => hash('sha256', strtolower($target->email).'|'.($target->school_id ?? 'platform')),
            'request_ip_hash' => $sourceIp === null ? null : hash('sha256', $sourceIp),
            'token_hash' => $tokenHash,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'request_count' => $requestCount,
            'request_window_started_at' => now(),
        ]);
        $metadata = $this->deliveryMetadata->summarize($target, [
            'purpose' => $purpose,
            'source' => 'administrator',
        ]);

        return [$candidate, $plainToken, $metadata];
    }
}
