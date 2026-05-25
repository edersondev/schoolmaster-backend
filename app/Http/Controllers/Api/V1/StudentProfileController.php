<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\StudentProfiles\CreateStudentProfileData;
use App\DTOs\StudentProfiles\TransferStudentProfileData;
use App\DTOs\StudentProfiles\UpdateStudentProfileStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentProfiles\CreateStudentProfileRequest;
use App\Http\Requests\StudentProfiles\GetStudentProfileRequest;
use App\Http\Requests\StudentProfiles\ListStudentProfilesRequest;
use App\Http\Requests\StudentProfiles\TransferStudentProfileRequest;
use App\Http\Requests\StudentProfiles\UpdateStudentProfileStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\StudentProfiles\StudentProfileLifecycleResource;
use App\Http\Resources\StudentProfiles\StudentProfileResource;
use App\Http\Resources\StudentProfiles\StudentProfileSummaryResource;
use App\Http\Resources\StudentProfiles\StudentTransferResource;
use App\Services\StudentProfiles\StudentProfileCreationService;
use App\Services\StudentProfiles\StudentProfileDetailService;
use App\Services\StudentProfiles\StudentProfileLifecycleService;
use App\Services\StudentProfiles\StudentProfileListService;
use App\Services\StudentProfiles\StudentProfileTransferService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;

final class StudentProfileController extends Controller
{
    public function __construct(
        private readonly StudentProfileListService $listService,
        private readonly StudentProfileCreationService $creationService,
        private readonly StudentProfileDetailService $detailService,
        private readonly StudentProfileLifecycleService $lifecycleService,
        private readonly StudentProfileTransferService $transferService,
        private readonly TenantContextService $tenantContext,
    ) {}

    public function index(ListStudentProfilesRequest $request): JsonResponse
    {
        $paginator = $this->listService->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, StudentProfileSummaryResource::collection($paginator->items())->resolve());
    }

    public function store(CreateStudentProfileRequest $request): JsonResponse
    {
        $school = $this->tenantContext->requireSchool($request->attributes->get('tenant_context'));

        $profile = $this->creationService->create(
            $request->attributes->get('auth_user'),
            $school,
            CreateStudentProfileData::fromArray($request->validated()),
        );

        return ApiResponse::success((new StudentProfileResource($profile))->resolve(), status: 201);
    }

    public function show(GetStudentProfileRequest $request, string $studentProfileId): JsonResponse
    {
        $profile = $this->detailService->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $studentProfileId,
        );

        return ApiResponse::success((new StudentProfileResource($profile))->resolve());
    }

    public function updateStatus(UpdateStudentProfileStatusRequest $request, string $studentProfileId): JsonResponse
    {
        $result = $this->lifecycleService->updateStatus(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $studentProfileId,
            UpdateStudentProfileStatusData::fromArray($request->validated()),
        );

        return ApiResponse::success((new StudentProfileLifecycleResource($result))->resolve());
    }

    public function transfer(TransferStudentProfileRequest $request, string $studentProfileId): JsonResponse
    {
        $result = $this->transferService->transferForContext(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $studentProfileId,
            TransferStudentProfileData::fromArray($request->validated()),
        );

        return ApiResponse::success((new StudentTransferResource($result))->resolve());
    }
}
