<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AccountLifecycle\AccountLockData;
use App\DTOs\AccountLifecycle\AccountRecoveryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountLifecycle\AccountLockRequest;
use App\Http\Requests\AccountLifecycle\AccountRecoveryRequest;
use App\Http\Resources\AccountRecoveryResource;
use App\Http\Resources\ApiResponse;
use App\Services\AccountLifecycle\AccountLockService;
use App\Services\AccountLifecycle\AccountRecoveryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountRecoveryController extends Controller
{
    public function __construct(
        private readonly AccountLockService $locks,
        private readonly AccountRecoveryService $recoveries,
        private readonly TenantContextResolver $tenantContext,
    ) {}

    public function show(Request $request, string $userId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $lock = $this->locks->show($actor, $context, $userId);

        return ApiResponse::success((new AccountRecoveryResource($lock))->resolve());
    }

    public function lock(AccountLockRequest $request, string $userId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $lock = $this->locks->lock(
            $actor,
            $context,
            new AccountLockData($userId, $request->validated('reason')),
            $request->ip(),
        );

        return ApiResponse::success((new AccountRecoveryResource($lock))->resolve());
    }

    public function unlock(Request $request, string $userId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $result = $this->locks->unlock($actor, $context, $userId, $request->ip());

        return ApiResponse::success($result);
    }

    public function recover(AccountRecoveryRequest $request, string $userId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $result = $this->recoveries->recover(
            $actor,
            $context,
            new AccountRecoveryData(
                userId: $userId,
                action: $request->validated('action'),
                reason: $request->validated('reason'),
            ),
            $request->ip(),
        );

        return ApiResponse::success($result);
    }
}
