<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTOs\AuditEventData;
use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\AuditEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RecordMasterAccessMutation
{
    public function __construct(private AuditEventService $audit) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $actor = $request->attributes->get('auth_user');

        if (! $this->shouldRecord($request, $response, $actor)) {
            return $response;
        }

        $tenantContext = $request->attributes->get('tenant_context');
        $routeName = (string) ($request->route()?->getName() ?? 'unnamed');

        $this->audit->record(new AuditEventData(
            eventType: 'master_access.mutation',
            outcome: 'success',
            actorUserId: $actor->id,
            schoolId: $tenantContext instanceof TenantContext ? $tenantContext->school?->id : null,
            affectedResourceType: 'route',
            affectedResourceId: $routeName,
            sourceIp: $request->ip(),
            metadata: [
                'http_method' => $request->method(),
                'route_name' => $routeName,
            ],
            masterAccessUsed: true,
        ));

        return $response;
    }

    private function shouldRecord(Request $request, Response $response, mixed $actor): bool
    {
        return $actor instanceof User
            && $actor->isSystemAdministrator()
            && ! $request->isMethodSafe()
            && $response->isSuccessful();
    }
}
