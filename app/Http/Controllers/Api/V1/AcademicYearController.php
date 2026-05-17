<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AcademicYears\CreateAcademicYearData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateAcademicYearRequest;
use App\Http\Resources\Api\V1\AcademicYearResource;
use App\Http\Resources\ApiResponse;
use App\Services\AcademicYears\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicYearService $academicYears) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->academicYears->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, AcademicYearResource::collection($paginator->items())->resolve());
    }

    public function store(CreateAcademicYearRequest $request): JsonResponse
    {
        $academicYear = $this->academicYears->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateAcademicYearData::fromArray($request->validated()),
        );

        return ApiResponse::success((new AcademicYearResource($academicYear))->resolve(), status: 201);
    }
}
