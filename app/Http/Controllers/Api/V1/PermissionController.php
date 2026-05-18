<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PermissionResource;
use App\Http\Resources\ApiResponse;
use App\Services\Permissions\PermissionQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PermissionController extends Controller
{
    public function __construct(private readonly PermissionQueryService $permissions) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->permissions->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, PermissionResource::collection($paginator->items())->resolve());
    }
}
