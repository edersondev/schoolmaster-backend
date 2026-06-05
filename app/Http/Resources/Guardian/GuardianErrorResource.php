<?php

declare(strict_types=1);

namespace App\Http\Resources\Guardian;

use App\Http\Resources\ApiResponse;
use Illuminate\Http\JsonResponse;

final class GuardianErrorResource
{
    public static function notFound(): JsonResponse
    {
        return ApiResponse::notFound();
    }

    public static function forbidden(): JsonResponse
    {
        return ApiResponse::forbidden();
    }
}
