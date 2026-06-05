<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Reports\ReportDefinitionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\CreateReportDefinitionRequest;
use App\Http\Requests\Reports\UpdateReportDefinitionRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Reports\ReportCatalogResource;
use App\Http\Resources\Reports\ReportDefinitionResource;
use App\Services\Reports\ReportCatalogService;
use App\Services\Reports\ReportDefinitionService;
use App\Services\Reports\ReportTenantContextService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class ReportDefinitionController extends Controller
{
    public function __construct(
        private readonly ReportTenantContextService $tenantContext,
        private readonly ReportCatalogService $catalog,
        private readonly ReportDefinitionService $definitions,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));

        return ApiResponse::success((new ReportCatalogResource($this->catalog->catalog($context)))->resolve());
    }

    public function index(Request $request): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $paginator = $this->definitions->list($context, $request->query());

        return ApiResponse::paginated($paginator, ReportDefinitionResource::collection($paginator->items())->resolve());
    }

    public function store(CreateReportDefinitionRequest $request): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->create($context, ReportDefinitionData::fromArray($request->validated()));

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve(), status: 201);
    }

    public function show(Request $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->find($context, $reportDefinitionId)->load(['school', 'creator', 'updater']);

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }

    public function update(UpdateReportDefinitionRequest $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->update($context, $reportDefinitionId, $request->validated());

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }

    public function activate(Request $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->activate($context, $reportDefinitionId);

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }

    public function deactivate(Request $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->deactivate($context, $reportDefinitionId);

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }

    public function delete(Request $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->delete($context, $reportDefinitionId);

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }

    public function restore(Request $request, string $reportDefinitionId): JsonResponse
    {
        $context = $this->tenantContext->resolve($request->attributes->get('auth_user'), $request->attributes->get('tenant_context'));
        $definition = $this->definitions->restore($context, $reportDefinitionId);

        return ApiResponse::success((new ReportDefinitionResource($definition))->resolve());
    }
}
