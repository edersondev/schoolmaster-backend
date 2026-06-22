<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Student;

use App\DTOs\Assessment\AssessmentResponseSubmissionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assessment\GetStudentQuestionnaireResponseRequest;
use App\Http\Requests\Api\V1\Assessment\SubmitStudentQuestionnaireResponseRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Assessment\StudentAssessmentResponseResource;
use App\Services\Assessment\AssessmentSubmissionService;
use App\Services\Assessment\StudentAssessmentResponseViewService;
use Illuminate\Http\JsonResponse;

final class StudentAssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentSubmissionService $submissions,
        private readonly StudentAssessmentResponseViewService $responses,
    ) {}

    public function store(SubmitStudentQuestionnaireResponseRequest $request): JsonResponse
    {
        $attempt = $this->submissions->submit(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            AssessmentResponseSubmissionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new StudentAssessmentResponseResource($attempt))->resolve(), status: 201);
    }

    public function show(GetStudentQuestionnaireResponseRequest $request, string $responseAttemptId): JsonResponse
    {
        $attempt = $this->responses->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $responseAttemptId,
        );

        return ApiResponse::success((new StudentAssessmentResponseResource($attempt))->resolve());
    }
}
