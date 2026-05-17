<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Guardians\CreateGuardianData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateGuardianRequest;
use App\Http\Resources\Api\V1\GuardianResource;
use App\Http\Resources\ApiResponse;
use App\Services\Guardians\GuardianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GuardianController extends Controller
{
    public function __construct(private readonly GuardianService $guardians) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->guardians->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, GuardianResource::collection($paginator->items())->resolve());
    }

    public function store(CreateGuardianRequest $request): JsonResponse
    {
        $guardian = $this->guardians->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateGuardianData::fromArray($request->validated()),
        );

        return ApiResponse::success((new GuardianResource($guardian))->resolve(), status: 201);
    }
}
