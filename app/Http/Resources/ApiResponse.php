<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, mixed $data): JsonResponse
    {
        return self::success($data, [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function error(string $code, string $message, array $details = [], int $status = 400, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function validation(array $errors): JsonResponse
    {
        return self::error('validation_failed', 'Validation failed.', ['fields' => $errors], 422);
    }

    public static function unauthorized(string $message = 'Authentication is missing or invalid.'): JsonResponse
    {
        return self::error('unauthorized', $message, [], 401);
    }

    public static function forbidden(string $message = 'The authenticated user lacks permission for this action.'): JsonResponse
    {
        return self::error('forbidden', $message, [], 403);
    }

    public static function tenantMismatch(string $message = 'Tenant context is missing, inactive, or outside permitted scope.'): JsonResponse
    {
        return self::error('tenant_mismatch', $message, [], 403);
    }

    public static function inactiveRecord(string $message = 'Resource is inactive for this workflow.'): JsonResponse
    {
        return self::error('inactive_record', $message, [], 409);
    }

    public static function lockout(int $retryAfterSeconds): JsonResponse
    {
        return self::error(
            'auth_locked',
            'Too many failed login attempts. Try again later.',
            ['retry_after_seconds' => $retryAfterSeconds],
            429,
            ['Retry-After' => (string) $retryAfterSeconds],
        );
    }

    public static function tokenRejected(string $code = 'token_revoked', string $message = 'Bearer token is expired, revoked, or inactive.'): JsonResponse
    {
        return self::error($code, $message, [], 401);
    }

    public static function notFound(string $message = 'Resource was not found in the permitted scope.'): JsonResponse
    {
        return self::error('not_found', $message, [], 404);
    }
}
