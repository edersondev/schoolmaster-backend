<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassroomRoster\ListClassSectionsRequest;
use App\Http\Requests\ClassroomRoster\StoreClassSectionRequest;
use App\Http\Requests\ClassroomRoster\UpdateClassSectionRequest;
use App\Http\Requests\ClassroomRoster\UpdateClassSectionStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ClassroomRoster\ClassSectionResource;
use App\Services\ClassroomRoster\ClassSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClassSectionController extends Controller
{
    public function __construct(private readonly ClassSectionService $classSections) {}

    public function index(ListClassSectionsRequest $request): JsonResponse
    {
        $paginator = $this->classSections->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, ClassSectionResource::collection($paginator->items())->resolve());
    }

    public function store(StoreClassSectionRequest $request): JsonResponse
    {
        $classSection = $this->classSections->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->all(),
        );

        return ApiResponse::success((new ClassSectionResource($classSection))->resolve(), status: 201);
    }

    public function show(Request $request, string $classSectionId): JsonResponse
    {
        $classSection = $this->classSections->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
        );

        return ApiResponse::success((new ClassSectionResource($classSection))->resolve());
    }

    public function update(UpdateClassSectionRequest $request, string $classSectionId): JsonResponse
    {
        $classSection = $this->classSections->update(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
            $request->all(),
        );

        return ApiResponse::success((new ClassSectionResource($classSection))->resolve());
    }

    public function updateStatus(UpdateClassSectionStatusRequest $request, string $classSectionId): JsonResponse
    {
        $classSection = $this->classSections->updateStatus(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
            $request->validated(),
        );

        return ApiResponse::success((new ClassSectionResource($classSection))->resolve());
    }
}
