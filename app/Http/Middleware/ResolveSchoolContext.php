<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveSchoolContext
{
    public function __construct(private readonly TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(
            'tenant_context',
            $this->resolver->resolve($request, $request->attributes->get('auth_user')),
        );

        return $next($request);
    }
}
