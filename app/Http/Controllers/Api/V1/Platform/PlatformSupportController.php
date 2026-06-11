<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Platform\ApproveSupportAccessRequest;
use App\Http\Requests\Api\V1\Platform\CreateSchoolSupportOptInRequest;
use App\Http\Requests\Api\V1\Platform\GetPlatformReportingOverviewRequest;
use App\Http\Requests\Api\V1\Platform\GetSupportSchoolDiagnosticsRequest;
use App\Http\Requests\Api\V1\Platform\ListPlatformSchoolSummariesRequest;
use App\Http\Requests\Api\V1\Platform\ListSupportAuditEventsRequest;
use App\Http\Requests\Api\V1\Platform\RequestSupportAccessRequest;
use App\Http\Requests\Api\V1\Platform\RevokeSchoolSupportOptInRequest;
use App\Http\Requests\Api\V1\Platform\RevokeSupportAccessRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Platform\PlatformReportingOverviewResource;
use App\Http\Resources\Platform\PlatformSchoolSummaryResource;
use App\Http\Resources\Platform\PlatformSupportAuditEventResource;
use App\Http\Resources\Platform\SchoolSupportOptInResource;
use App\Http\Resources\Platform\SupportAccessDecisionResource;
use App\Http\Resources\Platform\SupportDiagnosticResource;
use App\Services\PlatformSupport\InternalPlatformApprovalService;
use App\Services\PlatformSupport\PlatformReportingOverviewService;
use App\Services\PlatformSupport\PlatformSchoolSummaryService;
use App\Services\PlatformSupport\PlatformSupportAuditQueryService;
use App\Services\PlatformSupport\SchoolSupportOptInService;
use App\Services\PlatformSupport\SupportAccessDecisionService;
use App\Services\PlatformSupport\SupportDiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformSupportController extends Controller
{
    public function __construct(
        private readonly PlatformSchoolSummaryService $schoolSummaries,
        private readonly PlatformReportingOverviewService $reportingOverview,
        private readonly SupportAccessDecisionService $supportAccess,
        private readonly InternalPlatformApprovalService $approvals,
        private readonly SchoolSupportOptInService $schoolOptIns,
        private readonly SupportDiagnosticService $diagnostics,
        private readonly PlatformSupportAuditQueryService $auditEvents,
    ) {}

    public function schools(ListPlatformSchoolSummariesRequest $request): JsonResponse
    {
        $paginator = $this->schoolSummaries->list(
            $request->attributes->get('auth_user'),
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, PlatformSchoolSummaryResource::collection($paginator->items())->resolve());
    }

    public function reportingOverview(GetPlatformReportingOverviewRequest $request): JsonResponse
    {
        $overview = $this->reportingOverview->overview(
            $request->attributes->get('auth_user'),
            $request->validated(),
        );

        return ApiResponse::success((new PlatformReportingOverviewResource($overview))->resolve());
    }

    public function requestSupportAccess(RequestSupportAccessRequest $request): JsonResponse
    {
        $decision = $this->supportAccess->request($request->attributes->get('auth_user'), $request->validated());

        return ApiResponse::success((new SupportAccessDecisionResource($decision))->resolve(), status: 201);
    }

    public function showSupportAccess(Request $request, string $supportAccessId): JsonResponse
    {
        $decision = $this->supportAccess->get($request->attributes->get('auth_user'), $supportAccessId);

        return ApiResponse::success((new SupportAccessDecisionResource($decision))->resolve());
    }

    public function approveSupportAccess(ApproveSupportAccessRequest $request, string $supportAccessId): JsonResponse
    {
        $decision = $this->approvals->approve($request->attributes->get('auth_user'), $supportAccessId, $request->validated());

        return ApiResponse::success((new SupportAccessDecisionResource($decision))->resolve());
    }

    public function revokeSupportAccess(RevokeSupportAccessRequest $request, string $supportAccessId): JsonResponse
    {
        $decision = $this->approvals->revoke($request->attributes->get('auth_user'), $supportAccessId, $request->validated());

        return ApiResponse::success((new SupportAccessDecisionResource($decision))->resolve());
    }

    public function createSchoolSupportOptIn(CreateSchoolSupportOptInRequest $request, string $schoolId): JsonResponse
    {
        $optIn = $this->schoolOptIns->create($request->attributes->get('auth_user'), $schoolId, $request->validated());

        return ApiResponse::success((new SchoolSupportOptInResource($optIn))->resolve(), status: 201);
    }

    public function revokeSchoolSupportOptIn(RevokeSchoolSupportOptInRequest $request, string $schoolId, string $supportOptInId): JsonResponse
    {
        $optIn = $this->schoolOptIns->revoke($request->attributes->get('auth_user'), $schoolId, $supportOptInId, $request->validated());

        return ApiResponse::success((new SchoolSupportOptInResource($optIn))->resolve());
    }

    public function diagnostics(GetSupportSchoolDiagnosticsRequest $request, string $schoolId): JsonResponse
    {
        $diagnostics = $this->diagnostics->diagnostics($request->attributes->get('auth_user'), $schoolId, $request->validated());

        return ApiResponse::success((new SupportDiagnosticResource($diagnostics))->resolve());
    }

    public function auditEvents(ListSupportAuditEventsRequest $request): JsonResponse
    {
        $paginator = $this->auditEvents->list($request->attributes->get('auth_user'), $request->validated());

        return ApiResponse::paginated($paginator, PlatformSupportAuditEventResource::collection($paginator->items())->resolve());
    }
}
