<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LearningSets\CreateLearningSetData;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearningSets\CreateLearningSetRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\LearningSetResource;
use App\Services\LearningSets\LearningSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LearningSetController extends Controller
{
    public function __construct(private readonly LearningSetService $learningSets) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->learningSets->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, LearningSetResource::collection($paginator->items())->resolve());
    }

    public function store(CreateLearningSetRequest $request): JsonResponse
    {
        $learningSet = $this->learningSets->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateLearningSetData::fromArray($request->validated()),
        );

        return ApiResponse::success((new LearningSetResource($learningSet))->resolve(), status: 201);
    }
}
