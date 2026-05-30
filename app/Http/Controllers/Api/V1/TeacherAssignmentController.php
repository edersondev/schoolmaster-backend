<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassroomRoster\ListTeacherAssignmentsRequest;
use App\Http\Requests\ClassroomRoster\ShowTeacherAssignmentRequest;
use App\Http\Requests\ClassroomRoster\StoreTeacherAssignmentRequest;
use App\Http\Requests\ClassroomRoster\UpdateTeacherAssignmentStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ClassroomRoster\TeacherAssignmentResource;
use App\Services\ClassroomRoster\TeacherAssignmentService;
use Illuminate\Http\JsonResponse;

final class TeacherAssignmentController extends Controller
{
    public function __construct(private readonly TeacherAssignmentService $assignments) {}

    public function index(ListTeacherAssignmentsRequest $request): JsonResponse
    {
        $paginator = $this->assignments->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, TeacherAssignmentResource::collection($paginator->items())->resolve());
    }

    public function store(StoreTeacherAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->assignments->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->validated(),
        );

        return ApiResponse::success((new TeacherAssignmentResource($assignment))->resolve(), status: 201);
    }

    public function show(ShowTeacherAssignmentRequest $request, string $teacherAssignmentId): JsonResponse
    {
        $assignment = $this->assignments->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $teacherAssignmentId,
        );

        return ApiResponse::success((new TeacherAssignmentResource($assignment))->resolve());
    }

    public function updateStatus(UpdateTeacherAssignmentStatusRequest $request, string $teacherAssignmentId): JsonResponse
    {
        $assignment = $this->assignments->deactivate(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $teacherAssignmentId,
            $request->validated(),
        );

        return ApiResponse::success((new TeacherAssignmentResource($assignment))->resolve());
    }
}
