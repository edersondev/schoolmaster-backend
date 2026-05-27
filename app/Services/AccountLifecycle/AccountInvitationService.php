<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\DTOs\AccountLifecycle\CreateAccountInvitationData;
use App\DTOs\TenantContext;
use App\Exceptions\ConflictException;
use App\Exceptions\InactiveRecordException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TenantContextException;
use App\Models\AccountInvitation;
use App\Models\School;
use App\Models\User;
use App\Policies\AccountLifecyclePolicy;
use App\Repositories\AccountLifecycleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AccountInvitationService
{
    public function __construct(
        private readonly AccountLifecycleRepository $repository,
        private readonly LifecycleTokenService $tokens,
        private readonly AccountLifecyclePolicy $policy,
        private readonly AccountLifecycleAuditService $audit,
        private readonly EmailDeliveryRequestMetadataService $delivery,
    ) {}

    public function create(User $actor, TenantContext $context, CreateAccountInvitationData $data, ?string $sourceIp = null): AccountInvitation
    {
        $school = $this->schoolForScope($data->scope, $context, $data->schoolId);
        $this->authorize($actor, $data->scope, $school);

        $roles = $this->repository->activeRolesForScope($data->roleIds, $data->scope, $school?->id);
        if ($roles->count() !== count(array_unique($data->roleIds))) {
            throw ValidationException::withMessages(['role_ids' => ['All roles must be active and compatible with the invitation scope.']]);
        }

        return DB::transaction(function () use ($actor, $data, $school, $roles, $sourceIp): AccountInvitation {
            $user = $this->repository->findUserByEmailIncludingTrashed($data->email, $school?->id);

            if ($user !== null) {
                if ($user->trashed() || $user->status === 'inactive') {
                    throw new ConflictException('Inactive or deleted accounts cannot be invited.');
                }

                if ($user->status === 'active') {
                    throw new ConflictException('Existing active accounts cannot be invited again.');
                }

                if ($user->status !== 'invited') {
                    throw new ConflictException('Account is not eligible for invitation.');
                }
            }

            $user ??= User::query()->create([
                'school_id' => $school?->id,
                'name' => $data->fullName,
                'full_name' => $data->fullName,
                'email' => $data->email,
                'password' => Str::password(32),
                'status' => 'invited',
            ]);

            if ($user->trashed() || $user->school_id !== $school?->id) {
                throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
            }

            $user->roles()->sync($roles->pluck('id')->all());

            $recentSends = AccountInvitation::query()
                ->where('target_user_id', $user->id)
                ->where('scope', $data->scope)
                ->where('school_id', $school?->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($recentSends >= 3) {
                $this->audit->record('account_invitation_limited', 'failure', $user, $actor, $sourceIp);
                throw new ConflictException('Invitation send limit has been reached for this user and scope.');
            }

            AccountInvitation::query()
                ->where('target_user_id', $user->id)
                ->where('scope', $data->scope)
                ->where('school_id', $school?->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'superseded',
                    'superseded_at' => now(),
                ]);

            [, $tokenHash] = $this->tokens->issue();

            $invitation = AccountInvitation::query()->create([
                'target_user_id' => $user->id,
                'school_id' => $school?->id,
                'actor_user_id' => $actor->id,
                'scope' => $data->scope,
                'token_hash' => $tokenHash,
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'send_count' => $recentSends + 1,
                'send_window_started_at' => now(),
                'delivery_requested_at' => now(),
                'delivery_channel' => 'email',
                'email_delivery_metadata_summary' => $this->delivery->summarize($user, $data->deliveryMetadata + ['purpose' => 'account_invitation']),
            ]);

            $this->audit->record('account_invitation_created', 'success', $user, $actor, $sourceIp);

            return $invitation->load(['targetUser.school', 'school']);
        });
    }

    public function resend(User $actor, TenantContext $context, string $plainToken, ?string $sourceIp = null): AccountInvitation
    {
        $current = $this->repository->pendingInvitationByHash($this->tokens->hash($plainToken));

        if ($current === null || ! $current->isPending()) {
            throw new ConflictException('Invitation is not eligible for resend.');
        }

        $school = $this->schoolForScope($current->scope, $context, $current->school?->uuid);
        $this->authorize($actor, $current->scope, $school);

        return $this->create($actor, $context, new CreateAccountInvitationData(
            scope: $current->scope,
            fullName: $current->targetUser->full_name ?? $current->targetUser->name,
            email: $current->targetUser->email,
            roleIds: $current->targetUser->roles()->pluck('uuid')->all(),
            schoolId: $school?->uuid,
            deliveryMetadata: ['purpose' => 'account_invitation_resend'],
        ), $sourceIp);
    }

    private function authorize(User $actor, string $scope, ?School $school): void
    {
        if (! $this->policy->manage($actor, $scope, $school)) {
            throw new PermissionDeniedException('The authenticated user lacks permission for this action.');
        }
    }

    private function schoolForScope(string $scope, TenantContext $context, ?string $schoolUuid): ?School
    {
        if ($scope === 'platform') {
            if ($schoolUuid !== null || $context->school !== null) {
                throw ValidationException::withMessages(['scope' => ['Platform invitations cannot include school context.']]);
            }

            return null;
        }

        $school = $context->school;

        if ($school === null || $school->status !== 'active') {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        if ($schoolUuid !== null && $schoolUuid !== $school->uuid) {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        if ($school->status !== 'active') {
            throw new InactiveRecordException('Inactive schools cannot be used for account invitations.');
        }

        return $school;
    }
}
