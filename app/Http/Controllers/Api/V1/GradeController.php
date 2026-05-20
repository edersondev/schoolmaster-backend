<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Grades\CreateGradeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grades\CreateGradeRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\GradeRecordResource;
use App\Services\Grades\GradeRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GradeController extends Controller
{
    public function __construct(private readonly GradeRecordService $grades) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->grades->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, GradeRecordResource::collection($paginator->items())->resolve());
    }

    public function store(CreateGradeRequest $request): JsonResponse
    {
        $record = $this->grades->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateGradeData::fromArray($request->validated()),
        );

        return ApiResponse::success((new GradeRecordResource($record))->resolve(), status: 201);
    }
}
