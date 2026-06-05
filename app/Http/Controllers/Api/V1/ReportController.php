<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Reports\RequestReportData;
use App\DTOs\Reports\ReportLifecycleActionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CancelReportRequest;
use App\Http\Requests\Reports\DownloadReportRequest;
use App\Http\Requests\Reports\ListReportsRequest;
use App\Http\Requests\Reports\RequestReportRequest;
use App\Http\Requests\Reports\RetryReportRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Reports\ReportRunResource;
use App\Models\ReportOutput;
use App\Services\Reports\ReportDownloadService;
use App\Services\Reports\ReportLifecycleService;
use App\Services\Reports\ReportRequestService;
use App\Services\Reports\ReportRunListService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportRunListService $reportRuns,
        private readonly ReportRequestService $requests,
        private readonly ReportDownloadService $downloads,
        private readonly ReportLifecycleService $lifecycle,
    ) {}

    public function index(ListReportsRequest $request): JsonResponse
    {
        $paginator = $this->reportRuns->list($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'), $request->validated());

        return ApiResponse::paginated($paginator, ReportRunResource::collection($paginator->items())->resolve());
    }

    public function store(RequestReportRequest $request): JsonResponse
    {
        $run = $this->requests->request(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            RequestReportData::fromArray($request->all()),
        );

        return ApiResponse::success((new ReportRunResource($run))->resolve(), status: 202);
    }

    public function download(DownloadReportRequest $request, string $reportRunId): StreamedResponse
    {
        $output = $this->downloads->resolveDownload(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $reportRunId,
            $request->validated(),
        );

        if (! Storage::disk('report_outputs')->exists($output->storage_path)) {
            throw (new ModelNotFoundException)->setModel(ReportOutput::class);
        }

        return Storage::disk('report_outputs')->download($output->storage_path);
    }

    public function retry(RetryReportRequest $request, string $reportRunId): JsonResponse
    {
        $run = $this->lifecycle->retry(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $reportRunId,
            ReportLifecycleActionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new ReportRunResource($run))->resolve(), status: 202);
    }

    public function cancel(CancelReportRequest $request, string $reportRunId): JsonResponse
    {
        $run = $this->lifecycle->cancel(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $reportRunId,
            ReportLifecycleActionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new ReportRunResource($run))->resolve());
    }

    public function delete(Request $request, string $reportRunId): JsonResponse
    {
        $run = $this->lifecycle->delete(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $reportRunId,
        );

        return ApiResponse::success((new ReportRunResource($run))->resolve());
    }

    public function restore(Request $request, string $reportRunId): JsonResponse
    {
        $run = $this->lifecycle->restore(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $reportRunId,
        );

        return ApiResponse::success((new ReportRunResource($run))->resolve());
    }
}
