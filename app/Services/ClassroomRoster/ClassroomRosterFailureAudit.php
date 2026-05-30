<?php

declare(strict_types=1);

namespace App\Services\ClassroomRoster;

use App\DTOs\AuditEventData;
use App\DTOs\TenantContext;
use App\Models\User;
use App\Services\AuditEventService;
use Illuminate\Http\Request;

final readonly class ClassroomRosterFailureAudit
{
    public function __construct(private AuditEventService $auditEvents) {}

    public function supports(Request $request): bool
    {
        $routeName = $this->routeName($request);

        return str_starts_with($routeName, 'api.v1.class-sections.')
            || str_starts_with($routeName, 'api.v1.teacher-assignments.');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Request $request, string $outcome, array $metadata = []): void
    {
        if (! $this->supports($request)) {
            return;
        }

        $routeName = $this->routeName($request);
        /** @var User|null $actor */
        $actor = $request->attributes->get('auth_user');
        /** @var TenantContext|null $context */
        $context = $request->attributes->get('tenant_context');
        [$resourceType, $resourceId] = $this->targetFor($request);

        $this->auditEvents->record(new AuditEventData(
            eventType: 'classroom_roster.'.$routeName,
            outcome: $outcome,
            actorUserId: $actor?->id,
            schoolId: $context?->school?->id ?? $actor?->school_id,
            affectedResourceType: $resourceType,
            affectedResourceId: $resourceId,
            sourceIp: $request->ip(),
            metadata: array_merge(['route_name' => $routeName], $metadata),
        ));
    }

    private function routeName(Request $request): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        if ($routeName !== '') {
            return $routeName;
        }

        $path = trim($request->path(), '/');

        return match (true) {
            str_starts_with($path, 'api/v1/class-sections') => 'api.v1.class-sections.'.$this->pathSuffix($request, $path, 'api/v1/class-sections'),
            str_starts_with($path, 'api/v1/teacher-assignments') => 'api.v1.teacher-assignments.'.$this->pathSuffix($request, $path, 'api/v1/teacher-assignments'),
            default => '',
        };
    }

    private function pathSuffix(Request $request, string $path, string $prefix): string
    {
        $suffix = trim(substr($path, strlen($prefix)), '/');

        return match (true) {
            $suffix === '' && $request->isMethod('GET') => 'index',
            $suffix === '' && $request->isMethod('POST') => 'store',
            str_ends_with($suffix, '/status') => 'status.update',
            str_ends_with($suffix, '/memberships') && $request->isMethod('GET') => 'memberships.index',
            str_ends_with($suffix, '/memberships') && $request->isMethod('POST') => 'memberships.store',
            str_ends_with($suffix, '/memberships') && $request->isMethod('PATCH') => 'memberships.update',
            $request->isMethod('GET') => 'show',
            $request->isMethod('PATCH') => 'update',
            default => 'request',
        };
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function targetFor(Request $request): array
    {
        if ($request->route()?->parameter('teacherAssignmentId') !== null) {
            return ['teacher_assignment', (string) $request->route()->parameter('teacherAssignmentId')];
        }

        if ($request->route()?->parameter('classSectionId') !== null) {
            return ['class_section', (string) $request->route()->parameter('classSectionId')];
        }

        return [null, null];
    }
}
