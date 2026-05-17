<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use App\Http\Resources\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait WrapsApiResponses
{
    protected function paginatedResponse(LengthAwarePaginator $paginator, mixed $data): JsonResponse
    {
        return ApiResponse::paginated($paginator, $data);
    }
}
