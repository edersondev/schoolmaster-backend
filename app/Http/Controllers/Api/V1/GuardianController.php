<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Guardians\CreateGuardianData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateGuardianUserLinkRequest;
use App\Http\Requests\Api\V1\CreateGuardianRequest;
use App\Http\Requests\Api\V1\DeactivateGuardianUserLinkRequest;
use App\Http\Resources\Api\V1\GuardianResource;
use App\Http\Resources\Api\V1\GuardianUserLinkResource;
use App\Http\Resources\ApiResponse;
use App\Services\Guardians\GuardianService;
use App\Services\Guardians\GuardianUserLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GuardianController extends Controller
{
    public function __construct(
        private readonly GuardianService $guardians,
        private readonly GuardianUserLinkService $userLinks,
    ) {}

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

    public function createUserLink(CreateGuardianUserLinkRequest $request, string $guardianId): JsonResponse
    {
        $link = $this->userLinks->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $guardianId,
            $request->validated('user_id'),
            $request->validated('note'),
        );

        return ApiResponse::success((new GuardianUserLinkResource($link))->resolve(), status: 201);
    }

    public function deactivateUserLink(DeactivateGuardianUserLinkRequest $request, string $guardianId, string $guardianUserLinkId): JsonResponse
    {
        $link = $this->userLinks->deactivate(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $guardianId,
            $guardianUserLinkId,
            $request->validated('reason'),
        );

        return ApiResponse::success((new GuardianUserLinkResource($link))->resolve());
    }
}
