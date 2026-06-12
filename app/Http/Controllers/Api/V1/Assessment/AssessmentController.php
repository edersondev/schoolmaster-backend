<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assessment\DownloadAssessmentFileRequest;
use App\Http\Requests\Api\V1\Assessment\GradeAssessmentResponseRequest;
use App\Http\Requests\Api\V1\Assessment\ListQuestionnaireResponsesRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Assessment\AssessmentResponseReviewResource;
use App\Services\Assessment\AssessmentFileDownloadService;
use App\Services\Assessment\AssessmentGradingService;
use App\Services\Assessment\AssessmentResponseReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentResponseReviewService $reviews,
        private readonly AssessmentGradingService $grading,
        private readonly AssessmentFileDownloadService $downloads,
    ) {}

    public function index(ListQuestionnaireResponsesRequest $request): JsonResponse
    {
        $paginator = $this->reviews->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, AssessmentResponseReviewResource::collection($paginator->items())->resolve());
    }

    public function show(Request $request, string $responseAttemptId): JsonResponse
    {
        $attempt = $this->reviews->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $responseAttemptId,
        );

        return ApiResponse::success((new AssessmentResponseReviewResource($attempt))->resolve());
    }

    public function grade(GradeAssessmentResponseRequest $request, string $responseAttemptId): JsonResponse
    {
        $attempt = $this->grading->grade(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $responseAttemptId,
            $request->validated('grading_outcomes'),
        );

        return ApiResponse::success((new AssessmentResponseReviewResource($attempt))->resolve());
    }

    public function download(DownloadAssessmentFileRequest $request, string $responseAttemptId, string $fileId): StreamedResponse
    {
        return $this->downloads->download(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $responseAttemptId,
            $fileId,
        );
    }
}
