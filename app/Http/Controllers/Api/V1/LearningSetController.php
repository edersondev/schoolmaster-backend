<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LearningSets\CreateLearningSetData;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearningSets\CreateLearningSetRequest;
use App\Http\Requests\TeacherWorkflow\LearningSetRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\LearningSetResource;
use App\Http\Resources\TeacherWorkflow\LearningSetResource as TeacherWorkflowLearningSetResource;
use App\Services\LearningSets\LearningSetService as LearningSetCreateService;
use App\Services\TeacherWorkflow\LearningSetService as LearningSetLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LearningSetController extends Controller
{
    public function __construct(
        private readonly LearningSetCreateService $learningSets,
        private readonly LearningSetLifecycleService $learningSetLifecycle,
    ) {}

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

    public function show(Request $request, string $learningSetId): JsonResponse
    {
        $learningSet = $this->learningSetLifecycle->get($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $learningSetId);

        return ApiResponse::success((new TeacherWorkflowLearningSetResource($learningSet))->resolve());
    }

    public function update(LearningSetRequest $request, string $learningSetId): JsonResponse
    {
        $learningSet = $this->learningSetLifecycle->update($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $learningSetId, $request->validated());

        return ApiResponse::success((new TeacherWorkflowLearningSetResource($learningSet))->resolve());
    }

    public function updateStatus(LearningSetRequest $request, string $learningSetId): JsonResponse
    {
        $learningSet = $this->learningSetLifecycle->status($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $learningSetId, $request->validated('status'));

        return ApiResponse::success((new TeacherWorkflowLearningSetResource($learningSet))->resolve());
    }

    public function delete(Request $request, string $learningSetId): JsonResponse
    {
        $learningSet = $this->learningSetLifecycle->delete($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $learningSetId);

        return ApiResponse::success((new TeacherWorkflowLearningSetResource($learningSet))->resolve());
    }

    public function restore(LearningSetRequest $request, string $learningSetId): JsonResponse
    {
        $learningSet = $this->learningSetLifecycle->restore($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $learningSetId);

        return ApiResponse::success((new TeacherWorkflowLearningSetResource($learningSet))->resolve());
    }
}
