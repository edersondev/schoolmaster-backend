<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AccountLifecycle\CompletePasswordResetData;
use App\DTOs\AccountLifecycle\RequestPasswordResetData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountLifecycle\CompletePasswordResetRequest;
use App\Http\Requests\AccountLifecycle\RequestPasswordResetRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\PasswordResetResource;
use App\Services\AccountLifecycle\PasswordResetService;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResets) {}

    public function request(RequestPasswordResetRequest $request): JsonResponse
    {
        $this->passwordResets->request(
            RequestPasswordResetData::fromArray($request->validated()),
            $request->ip(),
        );

        return ApiResponse::success(PasswordResetResource::accepted(), status: 202);
    }

    public function complete(CompletePasswordResetRequest $request): JsonResponse
    {
        $result = $this->passwordResets->complete(new CompletePasswordResetData(
            token: $request->validated('token'),
            password: $request->validated('password'),
        ), $request->ip());

        return ApiResponse::success($result);
    }
}
