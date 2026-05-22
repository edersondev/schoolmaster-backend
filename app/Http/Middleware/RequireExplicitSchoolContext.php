<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTOs\TenantContext;
use App\Exceptions\TenantContextException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireExplicitSchoolContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get('tenant_context');

        if (! $context instanceof TenantContext || ! $context->isResolved() || $context->source !== 'x-school-id') {
            throw new TenantContextException('Tenant context is missing, inactive, or outside permitted scope.');
        }

        return $next($request);
    }
}
