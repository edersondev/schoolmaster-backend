<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;

final class AuthSessionResource
{
    public static function make(User $user, ?string $token, mixed $expiresAt): array
    {
        $user->loadMissing(['school', 'roles.permissions']);
        $permissions = $user->roles
            ->flatMap->permissions
            ->where('status', 'active')
            ->unique('id')
            ->values();

        return [
            'token' => $token,
            'token_expires_at' => $expiresAt?->toIso8601String(),
            'user' => (new UserResource($user))->resolve(),
            'resolved_school' => $user->school ? (new SchoolResource($user->school))->resolve() : null,
            'roles' => RoleResource::collection($user->roles)->resolve(),
            'permissions' => PermissionResource::collection($permissions)->resolve(),
        ];
    }
}
