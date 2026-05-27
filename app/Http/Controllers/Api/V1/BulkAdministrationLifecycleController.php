<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AdministrationLifecycle\ApplyBulkLifecycleActionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrationLifecycle\BulkLifecycleActionRequest;
use App\Http\Resources\AdministrationLifecycle\BulkLifecycleOutcomeResource;
use App\Http\Resources\ApiResponse;
use App\Services\AdministrationLifecycle\BulkAdministrationLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class BulkAdministrationLifecycleController extends Controller
{
    public function __construct(private readonly BulkAdministrationLifecycleService $bulkLifecycle) {}

    public function users(BulkLifecycleActionRequest $request): JsonResponse
    {
        return $this->apply($request, 'users');
    }

    public function roles(BulkLifecycleActionRequest $request): JsonResponse
    {
        return $this->apply($request, 'roles');
    }

    public function academicYears(BulkLifecycleActionRequest $request): JsonResponse
    {
        return $this->apply($request, 'academic_years');
    }

    public function academicPeriods(BulkLifecycleActionRequest $request): JsonResponse
    {
        return $this->apply($request, 'academic_periods');
    }

    public function guardians(BulkLifecycleActionRequest $request): JsonResponse
    {
        return $this->apply($request, 'guardians');
    }

    private function apply(BulkLifecycleActionRequest $request, string $expectedResourceType): JsonResponse
    {
        if ($request->validated('resource_type') !== $expectedResourceType) {
            throw ValidationException::withMessages([
                'resource_type' => ['The resource_type must match the bulk lifecycle endpoint.'],
            ]);
        }

        $result = $this->bulkLifecycle->apply(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            ApplyBulkLifecycleActionData::fromArray($request->validated()),
        );

        return ApiResponse::success((new BulkLifecycleOutcomeResource($result))->resolve());
    }
}
