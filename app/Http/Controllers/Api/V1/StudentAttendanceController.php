<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentSelfView\ListStudentAttendanceRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Student\StudentAttendanceRecordResource;
use App\Services\StudentSelfView\StudentAttendanceSelfViewService;
use Illuminate\Http\JsonResponse;

final class StudentAttendanceController extends Controller
{
    public function __construct(private readonly StudentAttendanceSelfViewService $attendance) {}

    public function index(ListStudentAttendanceRequest $request): JsonResponse
    {
        $paginator = $this->attendance->list($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $request->validated());

        return ApiResponse::paginated($paginator, StudentAttendanceRecordResource::collection($paginator->items())->resolve());
    }
}
