<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\AccountLifecycle\CompleteAccountInvitationData;
use App\DTOs\AccountLifecycle\CreateAccountInvitationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountLifecycle\CompleteAccountInvitationRequest;
use App\Http\Requests\AccountLifecycle\CreateAccountInvitationRequest;
use App\Http\Resources\AccountInvitationResource;
use App\Http\Resources\ApiResponse;
use App\Services\AccountLifecycle\AccountInvitationService;
use App\Services\AccountLifecycle\PasswordSetupService;
use App\Services\TenantContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountInvitationController extends Controller
{
    public function __construct(
        private readonly AccountInvitationService $invitations,
        private readonly PasswordSetupService $passwordSetup,
        private readonly TenantContextResolver $tenantContext,
    ) {}

    public function store(CreateAccountInvitationRequest $request): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $invitation = $this->invitations->create(
            $actor,
            $context,
            CreateAccountInvitationData::fromArray($request->validated()),
            $request->ip(),
        );

        return ApiResponse::success((new AccountInvitationResource($invitation))->resolve(), status: 201);
    }

    public function resend(Request $request, string $invitationToken): JsonResponse
    {
        $actor = $request->attributes->get('auth_user');
        $context = $this->tenantContext->resolve($request, $actor);
        $invitation = $this->invitations->resend($actor, $context, $invitationToken, $request->ip());

        return ApiResponse::success((new AccountInvitationResource($invitation))->resolve());
    }

    public function complete(CompleteAccountInvitationRequest $request, string $invitationToken): JsonResponse
    {
        $result = $this->passwordSetup->complete(new CompleteAccountInvitationData(
            token: $invitationToken,
            password: $request->validated('password'),
        ), $request->ip());

        return ApiResponse::success($result);
    }
}
