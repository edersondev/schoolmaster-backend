<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AccountLifecycle\CompletePasswordResetData;
use App\DTOs\AccountLifecycle\RequestPasswordResetData;
use App\Exceptions\ConflictException;
use App\Exceptions\TokenRejectedException;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class PasswordResetService
{
    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly LifecycleTokenService $tokens,
        private readonly BearerTokenRevocationService $bearerTokens,
        private readonly AccountLifecycleAuditService $audit,
        private readonly EmailDeliveryRequestMetadataService $delivery,
    ) {}

    public function request(RequestPasswordResetData $data, ?string $sourceIp = null): array
    {
        $school = $this->repository->findSchoolByUuid($data->schoolId);
        $schoolId = $school?->id;
        $identifierHash = $this->identifierHash($data->email, $schoolId);
        $requestIpHash = $sourceIp === null ? null : hash('sha256', $sourceIp);

        $latest = $this->repository->latestPendingResetForIdentifier($identifierHash, $requestIpHash);
        if ($this->isRequestSuppressed($latest)) {
            $this->audit->record('password_reset_request_suppressed', 'failure', $latest?->targetUser, sourceIp: $sourceIp);

            return ['accepted' => true];
        }

        $recentRequests = PasswordResetRequest::query()
            ->where(function ($query) use ($identifierHash, $requestIpHash): void {
                $query->where('account_identifier_hash', $identifierHash);

                if ($requestIpHash !== null) {
                    $query->orWhere('request_ip_hash', $requestIpHash);
                }
            })
            ->where('created_at', '>=', now()->subHour())
            ->whereNotNull('token_hash')
            ->count();

        if ($recentRequests >= 3) {
            $this->audit->record('password_reset_request_limited', 'failure', sourceIp: $sourceIp);

            return ['accepted' => true];
        }

        $user = $this->eligibleUser($data->email, $schoolId);
        if ($user === null) {
            $this->audit->record('password_reset_request_accepted', 'success', sourceIp: $sourceIp);

            return ['accepted' => true];
        }

        DB::transaction(function () use ($data, $user, $schoolId, $identifierHash, $requestIpHash, $sourceIp): void {
            PasswordResetRequest::query()
                ->where('target_user_id', $user->id)
                ->where('school_id', $schoolId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'superseded_at' => now(),
                ]);

            [, $tokenHash] = $this->tokens->issue();

            PasswordResetRequest::query()->create([
                'target_user_id' => $user->id,
                'school_id' => $schoolId,
                'account_identifier_hash' => $identifierHash,
                'request_ip_hash' => $requestIpHash,
                'token_hash' => $tokenHash,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(30),
                'request_count' => 1,
                'request_window_started_at' => now(),
                'delivery_requested_at' => now(),
                'delivery_channel' => 'email',
                'email_delivery_metadata_summary' => $this->delivery->summarize($user, $data->deliveryMetadata + ['purpose' => 'password_reset']),
            ]);

            $this->audit->record('password_reset_request_accepted', 'success', $user, sourceIp: $sourceIp);
        });

        return ['accepted' => true];
    }

    public function complete(CompletePasswordResetData $data, ?string $sourceIp = null): array
    {
        $reset = $this->repository->pendingResetByHash($this->tokens->hash($data->token));

        if ($reset === null) {
            throw new TokenRejectedException('token_invalid', 'Lifecycle token is invalid.');
        }

        if (! $reset->isPending()) {
            $this->recordFailure($reset, $sourceIp);
            throw new TokenRejectedException('token_invalid', 'Lifecycle token is invalid.');
        }

        return DB::transaction(function () use ($reset, $data, $sourceIp): array {
            $user = $reset->targetUser;

            if (! $user instanceof User || $user->status !== 'active') {
                throw new ConflictException('Account is not eligible for password reset.');
            }

            if ($user->school !== null && $user->school->status !== 'active') {
                throw new ConflictException('Inactive schools cannot complete password reset.');
            }

            if ($this->repository->activeAdministrativeLock($user) !== null) {
                throw new ConflictException('Locked accounts cannot complete password reset.');
            }

            $user->forceFill(['password' => Hash::make($data->password)])->save();
            $reset->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();
            $this->bearerTokens->revokeAllForUser($user);
            $this->audit->record('password_reset_completed', 'success', $user, sourceIp: $sourceIp);

            return [
                'user_id' => $user->uuid,
                'school_id' => $user->school?->uuid,
                'status' => $user->status,
                'action' => 'password_reset_completed',
            ];
        });
    }

    private function eligibleUser(string $email, ?int $schoolId): ?User
    {
        $user = $this->repository->findUserByEmail($email, $schoolId);

        if ($user === null || $user->trashed() || $user->status !== 'active') {
            return null;
        }

        if ($user->school !== null && $user->school->status !== 'active') {
            return null;
        }

        if ($this->repository->activeAdministrativeLock($user) !== null) {
            return null;
        }

        return $user;
    }

    private function identifierHash(string $email, ?int $schoolId): string
    {
        return hash('sha256', strtolower($email).'|'.($schoolId ?? 'platform'));
    }

    private function isRequestSuppressed(?PasswordResetRequest $latest): bool
    {
        return $latest !== null
            && $latest->suppressed_until !== null
            && $latest->suppressed_until->isFuture();
    }

    private function recordFailure(PasswordResetRequest $reset, ?string $sourceIp): void
    {
        $windowStarted = $reset->failed_completion_window_started_at;
        $count = $windowStarted !== null && $windowStarted->isAfter(now()->subMinutes(15))
            ? $reset->failed_completion_count + 1
            : 1;

        $reset->forceFill([
            'failed_completion_count' => $count,
            'failed_completion_window_started_at' => $count === 1 ? now() : $windowStarted,
            'suppressed_until' => $count >= 5 ? now()->addMinutes(15) : $reset->suppressed_until,
        ])->save();

        $this->audit->record('password_reset_completion_failed', 'failure', $reset->targetUser, sourceIp: $sourceIp);
    }
}
