<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\AuthSessionResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        [$user, $token, $expiresAt] = $this->auth->login($request->validated(), $request);

        return ApiResponse::success(AuthSessionResource::make($user, $token, $expiresAt));
    }

    public function me(Request $request): JsonResponse
    {
        [$user, $tenantContext] = $this->auth->currentUser($request);
        $resolvedSchool = $tenantContext->school;

        if ($user->isPlatformUser() && $resolvedSchool !== null && Gate::forUser($user)->denies('view', $resolvedSchool)) {
            $resolvedSchool = null;
        }

        return ApiResponse::success(AuthSessionResource::make(
            $user,
            $request->bearerToken(),
            $request->attributes->get('auth_token')->expires_at,
            $resolvedSchool,
        ));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request);

        return ApiResponse::success(['revoked' => true]);
    }
}
