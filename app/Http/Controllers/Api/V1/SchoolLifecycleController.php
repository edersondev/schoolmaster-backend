<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AdministrationLifecycle\ApplyLifecycleTransitionData;
use App\DTOs\AdministrationLifecycle\UpdateAdministrationResourceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrationLifecycle\ActivateAdministrationResourceRequest;
use App\Http\Requests\AdministrationLifecycle\DeactivateAdministrationResourceRequest;
use App\Http\Requests\AdministrationLifecycle\DeleteAdministrationResourceRequest;
use App\Http\Requests\AdministrationLifecycle\RestoreAdministrationResourceRequest;
use App\Http\Requests\AdministrationLifecycle\UpdateSchoolLifecycleRequest;
use App\Http\Resources\AdministrationLifecycle\LifecycleOutcomeResource;
use App\Http\Resources\AdministrationLifecycle\SchoolLifecycleResource;
use App\Http\Resources\ApiResponse;
use App\Services\AdministrationLifecycle\AdministrationDetailService;
use App\Services\AdministrationLifecycle\AdministrationLifecycleService;
use App\Services\AdministrationLifecycle\AdministrationUpdateService;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SchoolLifecycleController extends Controller
{
    public function __construct(
        private readonly AdministrationDetailService $detail,
        private readonly AdministrationUpdateService $updates,
        private readonly AdministrationLifecycleService $lifecycle,
    ) {}

    public function show(Request $request, string $schoolId): JsonResponse
    {
        $school = $this->detail->get($request->attributes->get('auth_user'), null, 'schools', $schoolId);

        return ApiResponse::success((new SchoolLifecycleResource($school))->resolve());
    }

    public function update(UpdateSchoolLifecycleRequest $request, string $schoolId): JsonResponse
    {
        $school = $this->updates->update(
            $request->attributes->get('auth_user'),
            null,
            'schools',
            $schoolId,
            UpdateAdministrationResourceData::fromArray($request->validated()),
            $request->ip(),
        );

        return ApiResponse::success((new SchoolLifecycleResource($school))->resolve());
    }

    public function activate(ActivateAdministrationResourceRequest $request, string $schoolId): JsonResponse
    {
        return $this->apply($request, $schoolId, LifecycleAction::ACTIVATE);
    }

    public function deactivate(DeactivateAdministrationResourceRequest $request, string $schoolId): JsonResponse
    {
        return $this->apply($request, $schoolId, LifecycleAction::DEACTIVATE);
    }

    public function delete(DeleteAdministrationResourceRequest $request, string $schoolId): JsonResponse
    {
        return $this->apply($request, $schoolId, LifecycleAction::DELETE);
    }

    public function restore(RestoreAdministrationResourceRequest $request, string $schoolId): JsonResponse
    {
        return $this->apply($request, $schoolId, LifecycleAction::RESTORE);
    }

    private function apply(Request $request, string $schoolId, string $action): JsonResponse
    {
        $result = $this->lifecycle->apply(
            $request->attributes->get('auth_user'),
            null,
            'schools',
            $schoolId,
            $action,
            ApplyLifecycleTransitionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new LifecycleOutcomeResource($result))->resolve());
    }
}
