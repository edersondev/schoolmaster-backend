<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TeacherWorkflow\AcademicRecordImportInput;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherWorkflow\AcademicRecordImportRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\TeacherWorkflow\AcademicRecordImportResource;
use App\Services\TeacherWorkflow\AcademicRecordImportService;
use Illuminate\Http\JsonResponse;

final class AcademicRecordImportController extends Controller
{
    public function __construct(
        private readonly AcademicRecordImportService $imports,
    ) {}

    public function importGrades(AcademicRecordImportRequest $request): JsonResponse
    {
        $run = $this->imports->import(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            AcademicRecordImportInput::grades($request->validated()),
        );

        return ApiResponse::success((new AcademicRecordImportResource($run))->resolve(), status: 201);
    }

    public function importAttendance(AcademicRecordImportRequest $request): JsonResponse
    {
        $run = $this->imports->import(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            AcademicRecordImportInput::attendance($request->validated()),
        );

        return ApiResponse::success((new AcademicRecordImportResource($run))->resolve(), status: 201);
    }
}
