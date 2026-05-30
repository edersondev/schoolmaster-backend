<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassroomRoster\BatchAddRosterMembershipsRequest;
use App\Http\Requests\ClassroomRoster\BatchEndRosterMembershipsRequest;
use App\Http\Requests\ClassroomRoster\ListRosterMembershipsRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\ClassroomRoster\RosterMembershipResource;
use App\Services\ClassroomRoster\RosterMembershipService;
use Illuminate\Http\JsonResponse;

final class RosterMembershipController extends Controller
{
    public function __construct(private readonly RosterMembershipService $memberships) {}

    public function index(ListRosterMembershipsRequest $request, string $classSectionId): JsonResponse
    {
        $paginator = $this->memberships->list(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
            $request->validated(),
        );

        return ApiResponse::paginated($paginator, RosterMembershipResource::collection($paginator->items())->resolve());
    }

    public function store(BatchAddRosterMembershipsRequest $request, string $classSectionId): JsonResponse
    {
        $memberships = $this->memberships->addBatch(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
            $request->validated(),
        );

        return ApiResponse::success([
            'affected_count' => count($memberships),
            'memberships' => RosterMembershipResource::collection($memberships)->resolve(),
        ], status: 201);
    }

    public function update(BatchEndRosterMembershipsRequest $request, string $classSectionId): JsonResponse
    {
        $memberships = $this->memberships->endBatch(
            $request->attributes->get('auth_user'),
            $request->attributes->get('tenant_context'),
            $classSectionId,
            $request->validated(),
        );

        return ApiResponse::success([
            'affected_count' => count($memberships),
            'memberships' => RosterMembershipResource::collection($memberships)->resolve(),
        ]);
    }
}
