<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountLifecycle\RequestPasswordDeliveryRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PasswordDeliveryRequestResource;
use App\Services\AccountLifecycle\PasswordDeliveryService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;

final class PasswordDeliveryController extends Controller
{
    public function __construct(
        private readonly PasswordDeliveryService $passwordDeliveries,
        private readonly TenantContextResolver $tenantContext,
    ) {}

    public function __invoke(RequestPasswordDeliveryRequest $request, string $userId): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $delivery = $this->passwordDeliveries->request(
            $actor,
            $context,
            $userId,
            $request->hasHeader('X-School-Id'),
            $request->ip(),
        );

        return ApiResponse::success(
            (new PasswordDeliveryRequestResource($delivery))->resolve(),
            status: 201,
        );
    }
}
