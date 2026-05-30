<?php

declare(strict_types=1);

namespace App\Http\Resources\ClassroomRoster;

use App\Http\Resources\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class BaseRosterResource
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $meta, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, mixed $data): JsonResponse
    {
        return ApiResponse::paginated($paginator, $data);
    }

    public static function validation(array $errors): JsonResponse
    {
        return ApiResponse::validation($errors);
    }

    public static function forbidden(): JsonResponse
    {
        return ApiResponse::forbidden();
    }

    public static function tenantMismatch(): JsonResponse
    {
        return ApiResponse::tenantMismatch();
    }

    public static function inactiveRecord(string $message = 'Resource is inactive for this workflow.'): JsonResponse
    {
        return ApiResponse::inactiveRecord($message);
    }

    public static function conflict(string $message = 'Request conflicts with existing classroom roster state.'): JsonResponse
    {
        return ApiResponse::error('conflict', $message, [], 409);
    }

    public static function unsupportedQuery(): JsonResponse
    {
        return ApiResponse::validation([
            'query' => ['Unsupported filter, include, sort, or page-size parameter.'],
        ]);
    }

    public static function notFound(): JsonResponse
    {
        return ApiResponse::notFound();
    }
}
