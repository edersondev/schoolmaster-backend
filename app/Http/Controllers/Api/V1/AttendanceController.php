<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Attendance\CreateAttendanceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CreateAttendanceRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\AttendanceRecordResource;
use App\Services\Attendance\AttendanceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceRecordService $attendance) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->attendance->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->query(),
        );

        return ApiResponse::paginated($paginator, AttendanceRecordResource::collection($paginator->items())->resolve());
    }

    public function store(CreateAttendanceRequest $request): JsonResponse
    {
        $record = $this->attendance->create(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            CreateAttendanceData::fromArray($request->validated()),
        );

        return ApiResponse::success((new AttendanceRecordResource($record))->resolve(), status: 201);
    }
}
