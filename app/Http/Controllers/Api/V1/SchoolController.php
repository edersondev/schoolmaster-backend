<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schools\StoreSchoolRequest;
use App\Http\Requests\Schools\UpdateSchoolRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\SchoolResource;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SchoolController extends Controller
{
    public function __construct(private readonly SchoolService $schools) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->schools->list($request->attributes->get('auth_user'), $request->query());

        return ApiResponse::paginated($paginator, SchoolResource::collection($paginator->items())->resolve());
    }

    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $school = $this->schools->create($request->attributes->get('auth_user'), $request->validated(), $request->ip());

        return ApiResponse::success((new SchoolResource($school))->resolve(), status: 201);
    }

    public function show(Request $request, string $schoolId): JsonResponse
    {
        $school = $this->schools->get($request->attributes->get('auth_user'), $schoolId);

        return ApiResponse::success((new SchoolResource($school))->resolve());
    }

    public function update(UpdateSchoolRequest $request, string $schoolId): JsonResponse
    {
        $school = $this->schools->update($request->attributes->get('auth_user'), $schoolId, $request->validated(), $request->ip());

        return ApiResponse::success((new SchoolResource($school))->resolve());
    }
}
