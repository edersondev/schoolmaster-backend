<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TeacherWorkflow\CorrectionInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherWorkflow\AcademicRecordRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TeacherWorkflow\AcademicRecordResource;
use App\Services\TeacherWorkflow\AcademicRecordCorrectionService;
use App\Services\TeacherWorkflow\AcademicRecordLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AcademicRecordController extends Controller
{
    public function __construct(
        private readonly AcademicRecordLifecycleService $lifecycle,
        private readonly AcademicRecordCorrectionService $corrections,
    ) {}

    public function showGrade(Request $request, string $gradeId): JsonResponse
    {
        $record = $this->lifecycle->getGrade($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $gradeId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function correctGrade(AcademicRecordRequest $request, string $gradeId): JsonResponse
    {
        $record = $this->corrections->correctGrade($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $gradeId, CorrectionInput::grade($request->validated()));

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function updateGradeStatus(AcademicRecordRequest $request, string $gradeId): JsonResponse
    {
        $record = $this->lifecycle->status($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'grade', $gradeId, $request->validated('status'));

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function deleteGrade(Request $request, string $gradeId): JsonResponse
    {
        $record = $this->lifecycle->delete($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'grade', $gradeId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function restoreGrade(AcademicRecordRequest $request, string $gradeId): JsonResponse
    {
        $record = $this->lifecycle->restore($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'grade', $gradeId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function showAttendance(Request $request, string $attendanceId): JsonResponse
    {
        $record = $this->lifecycle->getAttendance($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $attendanceId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function correctAttendance(AcademicRecordRequest $request, string $attendanceId): JsonResponse
    {
        $record = $this->corrections->correctAttendance($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $attendanceId, CorrectionInput::attendance($request->validated()));

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function updateAttendanceStatus(AcademicRecordRequest $request, string $attendanceId): JsonResponse
    {
        $record = $this->lifecycle->status($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'attendance', $attendanceId, $request->validated('status'));

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function deleteAttendance(Request $request, string $attendanceId): JsonResponse
    {
        $record = $this->lifecycle->delete($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'attendance', $attendanceId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }

    public function restoreAttendance(AcademicRecordRequest $request, string $attendanceId): JsonResponse
    {
        $record = $this->lifecycle->restore($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), 'attendance', $attendanceId);

        return ApiResponse::success((new AcademicRecordResource($record))->resolve());
    }
}
