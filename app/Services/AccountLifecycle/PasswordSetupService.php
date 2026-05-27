<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AccountLifecycle\CompleteAccountInvitationData;
use App\Exceptions\ConflictException;
use App\Exceptions\TokenRejectedException;
use App\Models\AccountInvitation;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class PasswordSetupService
{
    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly LifecycleTokenService $tokens,
        private readonly AccountLifecycleAuditService $audit,
    ) {}

    public function complete(CompleteAccountInvitationData $data, ?string $sourceIp = null): array
    {
        $invitation = $this->repository->pendingInvitationByHash($this->tokens->hash($data->token));

        if ($invitation === null) {
            throw new TokenRejectedException('token_invalid', 'Lifecycle token is invalid.');
        }

        if (! $invitation->isPending()) {
            $this->recordFailure($invitation, $sourceIp);
            throw new TokenRejectedException('token_invalid', 'Lifecycle token is invalid.');
        }

        return DB::transaction(function () use ($invitation, $data, $sourceIp): array {
            $user = $invitation->targetUser;

            if ($user->status === 'active') {
                throw new ConflictException('Account has already completed password setup.');
            }

            if ($user->school !== null && $user->school->status !== 'active') {
                throw new ConflictException('Inactive schools cannot complete account setup.');
            }

            $user->forceFill([
                'password' => Hash::make($data->password),
                'status' => 'active',
            ])->save();

            $invitation->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();

            $this->audit->record('account_invitation_completed', 'success', $user, sourceIp: $sourceIp);

            return [
                'user_id' => $user->uuid,
                'school_id' => $user->school?->uuid,
                'status' => $user->status,
                'action' => 'password_setup_completed',
            ];
        });
    }

    private function recordFailure(AccountInvitation $invitation, ?string $sourceIp): void
    {
        $windowStarted = $invitation->failed_completion_window_started_at;
        $count = $windowStarted !== null && $windowStarted->isAfter(now()->subMinutes(15))
            ? $invitation->failed_completion_count + 1
            : 1;

        $invitation->forceFill([
            'failed_completion_count' => $count,
            'failed_completion_window_started_at' => $count === 1 ? now() : $windowStarted,
            'last_failed_completion_ip' => $sourceIp,
            'status' => $count >= 5 ? 'revoked' : $invitation->status,
            'revoked_at' => $count >= 5 ? now() : $invitation->revoked_at,
        ])->save();

        $this->audit->record('account_invitation_completion_failed', 'failure', $invitation->targetUser, sourceIp: $sourceIp);
    }
}
