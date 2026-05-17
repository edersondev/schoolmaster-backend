<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTOs\AuditEventData;
use App\Exceptions\TokenRejectedException;
use App\Services\AuditEventService;
use App\Services\AuthTokenLifecycleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateBearerToken
{
    public function __construct(
        private readonly AuthTokenLifecycleService $tokens,
        private readonly AuditEventService $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $this->tokens->resolve($request->bearerToken());
        } catch (TokenRejectedException $exception) {
            $this->audit->record(new AuditEventData(
                eventType: 'token_rejected',
                outcome: 'failure',
                sourceIp: $request->ip(),
                metadata: ['reason' => $exception->reasonCode()],
            ));

            throw $exception;
        }

        $request->attributes->set('auth_token', $token);
        $request->attributes->set('auth_user', $token->user);

        return $next($request);
    }
}
