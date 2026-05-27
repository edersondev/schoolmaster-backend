<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AccountInvitation;
use App\Models\AccountLock;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AccountLifecycleRepository
{
    public function findSchoolByUuid(?string $uuid): ?School
    {
        if ($uuid === null) {
            return null;
        }

        return School::query()->where('uuid', $uuid)->first();
    }

    public function findUserByUuid(string $uuid): ?User
    {
        return User::query()->with(['school', 'roles.permissions'])->where('uuid', $uuid)->first();
    }

    public function findUserByEmail(string $email, ?int $schoolId): ?User
    {
        return User::query()
            ->with(['school', 'roles.permissions'])
            ->where('email', strtolower($email))
            ->where('school_id', $schoolId)
            ->first();
    }

    public function findUserByEmailIncludingTrashed(string $email, ?int $schoolId): ?User
    {
        return User::query()
            ->with(['school', 'roles.permissions'])
            ->withTrashed()
            ->where('email', strtolower($email))
            ->where(function (Builder $query) use ($schoolId): void {
                if ($schoolId === null) {
                    $query->whereNull('school_id');

                    return;
                }

                $query->where('school_id', $schoolId);
            })
            ->first();
    }

    /**
     * @param  array<int, string>  $roleUuids
     * @return Collection<int, Role>
     */
    public function activeRolesForScope(array $roleUuids, string $scope, ?int $schoolId): Collection
    {
        return Role::query()
            ->whereIn('uuid', $roleUuids)
            ->where('status', 'active')
            ->where('scope', $scope)
            ->when($scope === 'school', fn ($query) => $query->where('school_id', $schoolId))
            ->when($scope === 'platform', fn ($query) => $query->whereNull('school_id'))
            ->get();
    }

    public function pendingInvitationByHash(string $tokenHash): ?AccountInvitation
    {
        return AccountInvitation::query()
            ->with(['targetUser.school', 'targetUser.roles.permissions', 'school'])
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function pendingResetByHash(string $tokenHash): ?PasswordResetRequest
    {
        return PasswordResetRequest::query()
            ->with(['targetUser.school', 'targetUser.roles.permissions', 'school'])
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function activeAdministrativeLock(User $user): ?AccountLock
    {
        return AccountLock::query()
            ->where('user_id', $user->id)
            ->where('lock_type', 'administrative')
            ->where('status', 'active')
            ->whereNull('cleared_at')
            ->latest('locked_at')
            ->first();
    }

    public function latestPendingResetForIdentifier(string $identifierHash, ?string $requestIpHash): ?PasswordResetRequest
    {
        return PasswordResetRequest::query()
            ->where(function ($query) use ($identifierHash, $requestIpHash): void {
                $query->where('account_identifier_hash', $identifierHash);

                if ($requestIpHash !== null) {
                    $query->orWhere('request_ip_hash', $requestIpHash);
                }
            })
            ->latest()
            ->first();
    }
}
