<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Roles\CreateRoleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Http\Resources\ApiResponse;
use App\Services\Roles\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->roles->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, RoleResource::collection($paginator->items())->resolve());
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $role = $this->roles->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateRoleData::fromArray($request->validated()),
        );

        return ApiResponse::success((new RoleResource($role))->resolve(), status: 201);
    }
}
