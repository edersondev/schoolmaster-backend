<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Users\CreateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\ApiResponse;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->users->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, UserResource::collection($paginator->items())->resolve());
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->users->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateUserData::fromArray($request->validated()),
        );

        return ApiResponse::success((new UserResource($user))->resolve(), status: 201);
    }
}
