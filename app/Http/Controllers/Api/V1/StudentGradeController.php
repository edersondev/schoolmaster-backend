<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentSelfView\ListStudentGradesRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Student\StudentGradeRecordResource;
use App\Services\StudentSelfView\StudentGradeSelfViewService;
use Illuminate\Http\JsonResponse;

final class StudentGradeController extends Controller
{
    public function __construct(private readonly StudentGradeSelfViewService $grades) {}

    public function index(ListStudentGradesRequest $request): JsonResponse
    {
        $paginator = $this->grades->list($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $request->validated());

        return ApiResponse::paginated($paginator, StudentGradeRecordResource::collection($paginator->items())->resolve());
    }
}
