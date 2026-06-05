<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\TenantContextException;
use App\Models\School;
use App\Models\User;
use App\Services\GuardianSelfService\GuardianAuditService;
use App\Services\TenantContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveSchoolContext
{
    public function __construct(private readonly TenantContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $request->attributes->set(
                'tenant_context',
                $this->resolver->resolve($request, $request->attributes->get('auth_user')),
            );
        } catch (TenantContextException $exception) {
            $action = $this->guardianSelfServiceAction($request);

            if ($action !== null) {
                $actor = $request->attributes->get('auth_user');

                app(GuardianAuditService::class)->denied(
                    $request,
                    $action,
                    'tenant_context_unresolved',
                    $actor,
                    $this->schoolForAudit($actor),
                );
            }

            throw $exception;
        }

        return $next($request);
    }

    private function guardianSelfServiceAction(Request $request): ?string
    {
        if (! $request->is('api/v1/guardian/students*')) {
            return null;
        }

        $path = $request->path();

        return match (true) {
            str_ends_with($path, '/academics') => 'academic_summary',
            str_ends_with($path, '/contacts') => 'contact_view',
            $path === 'api/v1/guardian/students' => 'student_list',
            default => 'student_detail',
        };
    }

    private function schoolForAudit(mixed $actor): ?School
    {
        if (! $actor instanceof User || $actor->school_id === null) {
            return null;
        }

        $actor->loadMissing('school');

        return $actor->school;
    }
}
