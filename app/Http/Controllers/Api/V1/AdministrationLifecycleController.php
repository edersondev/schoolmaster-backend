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
use App\Http\Requests\AdministrationLifecycle\UpdateAcademicPeriodLifecycleRequest;
use App\Http\Requests\AdministrationLifecycle\UpdateAcademicYearLifecycleRequest;
use App\Http\Requests\AdministrationLifecycle\UpdateGuardianLifecycleRequest;
use App\Http\Requests\AdministrationLifecycle\UpdateRoleLifecycleRequest;
use App\Http\Requests\AdministrationLifecycle\UpdateUserLifecycleRequest;
use App\Http\Resources\AdministrationLifecycle\AcademicPeriodLifecycleResource;
use App\Http\Resources\AdministrationLifecycle\AcademicYearLifecycleResource;
use App\Http\Resources\AdministrationLifecycle\GuardianLifecycleResource;
use App\Http\Resources\AdministrationLifecycle\LifecycleOutcomeResource;
use App\Http\Resources\AdministrationLifecycle\RoleLifecycleResource;
use App\Http\Resources\AdministrationLifecycle\UserLifecycleResource;
use App\Http\Resources\ApiResponse;
use App\Services\AdministrationLifecycle\AdministrationDetailService;
use App\Services\AdministrationLifecycle\AdministrationLifecycleService;
use App\Services\AdministrationLifecycle\AdministrationUpdateService;
use App\Services\AdministrationLifecycle\LifecycleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdministrationLifecycleController extends Controller
{
    public function __construct(
        private readonly AdministrationDetailService $detail,
        private readonly AdministrationUpdateService $updates,
        private readonly AdministrationLifecycleService $lifecycle,
    ) {}

    public function showUser(Request $request, string $userId): JsonResponse
    {
        return $this->show($request, 'users', $userId);
    }

    public function updateUser(UpdateUserLifecycleRequest $request, string $userId): JsonResponse
    {
        return $this->update($request, 'users', $userId);
    }

    public function showRole(Request $request, string $roleId): JsonResponse
    {
        return $this->show($request, 'roles', $roleId);
    }

    public function updateRole(UpdateRoleLifecycleRequest $request, string $roleId): JsonResponse
    {
        return $this->update($request, 'roles', $roleId);
    }

    public function showAcademicYear(Request $request, string $academicYearId): JsonResponse
    {
        return $this->show($request, 'academic_years', $academicYearId);
    }

    public function updateAcademicYear(UpdateAcademicYearLifecycleRequest $request, string $academicYearId): JsonResponse
    {
        return $this->update($request, 'academic_years', $academicYearId);
    }

    public function showAcademicPeriod(Request $request, string $academicPeriodId): JsonResponse
    {
        return $this->show($request, 'academic_periods', $academicPeriodId);
    }

    public function updateAcademicPeriod(UpdateAcademicPeriodLifecycleRequest $request, string $academicPeriodId): JsonResponse
    {
        return $this->update($request, 'academic_periods', $academicPeriodId);
    }

    public function showGuardian(Request $request, string $guardianId): JsonResponse
    {
        return $this->show($request, 'guardians', $guardianId);
    }

    public function updateGuardian(UpdateGuardianLifecycleRequest $request, string $guardianId): JsonResponse
    {
        return $this->update($request, 'guardians', $guardianId);
    }

    public function activateUser(ActivateAdministrationResourceRequest $request, string $userId): JsonResponse
    {
        return $this->apply($request, 'users', $userId, LifecycleAction::ACTIVATE);
    }

    public function deactivateUser(DeactivateAdministrationResourceRequest $request, string $userId): JsonResponse
    {
        return $this->apply($request, 'users', $userId, LifecycleAction::DEACTIVATE);
    }

    public function deleteUser(DeleteAdministrationResourceRequest $request, string $userId): JsonResponse
    {
        return $this->apply($request, 'users', $userId, LifecycleAction::DELETE);
    }

    public function restoreUser(RestoreAdministrationResourceRequest $request, string $userId): JsonResponse
    {
        return $this->apply($request, 'users', $userId, LifecycleAction::RESTORE);
    }

    public function activateRole(ActivateAdministrationResourceRequest $request, string $roleId): JsonResponse
    {
        return $this->apply($request, 'roles', $roleId, LifecycleAction::ACTIVATE);
    }

    public function deactivateRole(DeactivateAdministrationResourceRequest $request, string $roleId): JsonResponse
    {
        return $this->apply($request, 'roles', $roleId, LifecycleAction::DEACTIVATE);
    }

    public function deleteRole(DeleteAdministrationResourceRequest $request, string $roleId): JsonResponse
    {
        return $this->apply($request, 'roles', $roleId, LifecycleAction::DELETE);
    }

    public function restoreRole(RestoreAdministrationResourceRequest $request, string $roleId): JsonResponse
    {
        return $this->apply($request, 'roles', $roleId, LifecycleAction::RESTORE);
    }

    public function activateAcademicYear(ActivateAdministrationResourceRequest $request, string $academicYearId): JsonResponse
    {
        return $this->apply($request, 'academic_years', $academicYearId, LifecycleAction::ACTIVATE);
    }

    public function deactivateAcademicYear(DeactivateAdministrationResourceRequest $request, string $academicYearId): JsonResponse
    {
        return $this->apply($request, 'academic_years', $academicYearId, LifecycleAction::DEACTIVATE);
    }

    public function deleteAcademicYear(DeleteAdministrationResourceRequest $request, string $academicYearId): JsonResponse
    {
        return $this->apply($request, 'academic_years', $academicYearId, LifecycleAction::DELETE);
    }

    public function restoreAcademicYear(RestoreAdministrationResourceRequest $request, string $academicYearId): JsonResponse
    {
        return $this->apply($request, 'academic_years', $academicYearId, LifecycleAction::RESTORE);
    }

    public function activateAcademicPeriod(ActivateAdministrationResourceRequest $request, string $academicPeriodId): JsonResponse
    {
        return $this->apply($request, 'academic_periods', $academicPeriodId, LifecycleAction::ACTIVATE);
    }

    public function deactivateAcademicPeriod(DeactivateAdministrationResourceRequest $request, string $academicPeriodId): JsonResponse
    {
        return $this->apply($request, 'academic_periods', $academicPeriodId, LifecycleAction::DEACTIVATE);
    }

    public function deleteAcademicPeriod(DeleteAdministrationResourceRequest $request, string $academicPeriodId): JsonResponse
    {
        return $this->apply($request, 'academic_periods', $academicPeriodId, LifecycleAction::DELETE);
    }

    public function restoreAcademicPeriod(RestoreAdministrationResourceRequest $request, string $academicPeriodId): JsonResponse
    {
        return $this->apply($request, 'academic_periods', $academicPeriodId, LifecycleAction::RESTORE);
    }

    public function activateGuardian(ActivateAdministrationResourceRequest $request, string $guardianId): JsonResponse
    {
        return $this->apply($request, 'guardians', $guardianId, LifecycleAction::ACTIVATE);
    }

    public function deactivateGuardian(DeactivateAdministrationResourceRequest $request, string $guardianId): JsonResponse
    {
        return $this->apply($request, 'guardians', $guardianId, LifecycleAction::DEACTIVATE);
    }

    public function deleteGuardian(DeleteAdministrationResourceRequest $request, string $guardianId): JsonResponse
    {
        return $this->apply($request, 'guardians', $guardianId, LifecycleAction::DELETE);
    }

    public function restoreGuardian(RestoreAdministrationResourceRequest $request, string $guardianId): JsonResponse
    {
        return $this->apply($request, 'guardians', $guardianId, LifecycleAction::RESTORE);
    }

    private function show(Request $request, string $resourceType, string $resourceId): JsonResponse
    {
        $resource = $this->detail->get(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $resourceType,
            $resourceId,
        );

        return ApiResponse::success($this->resource($resourceType, $resource)->resolve());
    }

    private function update(Request $request, string $resourceType, string $resourceId): JsonResponse
    {
        $resource = $this->updates->update(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $resourceType,
            $resourceId,
            UpdateAdministrationResourceData::fromArray($request->validated()),
            $request->ip(),
        );

        return ApiResponse::success($this->resource($resourceType, $resource)->resolve());
    }

    private function apply(Request $request, string $resourceType, string $resourceId, string $action): JsonResponse
    {
        $result = $this->lifecycle->apply(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $resourceType,
            $resourceId,
            $action,
            ApplyLifecycleTransitionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new LifecycleOutcomeResource($result))->resolve());
    }

    private function resource(string $resourceType, mixed $resource): JsonResource
    {
        return match ($resourceType) {
            'users' => new UserLifecycleResource($resource),
            'roles' => new RoleLifecycleResource($resource),
            'academic_years' => new AcademicYearLifecycleResource($resource),
            'academic_periods' => new AcademicPeriodLifecycleResource($resource),
            'guardians' => new GuardianLifecycleResource($resource),
        };
    }
}
