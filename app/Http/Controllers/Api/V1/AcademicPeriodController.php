<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AcademicPeriods\CreateAcademicPeriodData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateAcademicPeriodRequest;
use App\Http\Resources\Api\V1\AcademicPeriodResource;
use App\Http\Resources\ApiResponse;
use App\Services\AcademicPeriods\AcademicPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AcademicPeriodController extends Controller
{
    public function __construct(private readonly AcademicPeriodService $academicPeriods) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->academicPeriods->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, AcademicPeriodResource::collection($paginator->items())->resolve());
    }

    public function store(CreateAcademicPeriodRequest $request): JsonResponse
    {
        $academicPeriod = $this->academicPeriods->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateAcademicPeriodData::fromArray($request->validated()),
        );

        return ApiResponse::success((new AcademicPeriodResource($academicPeriod))->resolve(), status: 201);
    }
}
