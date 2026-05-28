<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;

final class AccountLifecycleResource
{
    public static function success(array $data = [], int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, status: $status);
    }

    public static function accepted(): JsonResponse
    {
        return ApiResponse::success(['accepted' => true], status: 202);
    }

    public static function tokenInvalid(string $message = 'Lifecycle token is invalid.'): JsonResponse
    {
        return ApiResponse::error('token_invalid', $message, [], 401);
    }

    public static function conflict(string $message): JsonResponse
    {
        return ApiResponse::error('conflict', $message, [], 409);
    }
}
