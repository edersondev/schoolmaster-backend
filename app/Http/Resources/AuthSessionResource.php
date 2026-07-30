<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\School;
use App\Models\User;

final class AuthSessionResource
{
    public static function make(
        User $user,
        ?string $token,
        mixed $expiresAt,
        ?School $resolvedSchool = null,
    ): array {
        $user->loadMissing(['school.address', 'roles.permissions']);
        $resolvedSchool ??= $user->school;
        $resolvedSchool?->loadMissing('address');
        $permissions = $user->roles
            ->flatMap->permissions
            ->where('status', 'active')
            ->unique('id')
            ->values();

        return [
            'token' => $token,
            'token_expires_at' => $expiresAt?->toIso8601String(),
            'user' => (new UserResource($user))->resolve(),
            'resolved_school' => $resolvedSchool ? (new SchoolResource($resolvedSchool))->resolve() : null,
            'roles' => RoleResource::collection($user->roles)->resolve(),
            'permissions' => PermissionResource::collection($permissions)->resolve(),
        ];
    }
}
