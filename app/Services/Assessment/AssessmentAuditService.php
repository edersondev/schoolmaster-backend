<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\DTOs\Assessment\AssessmentActorContext;
use App\DTOs\TenantContext;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AssessmentAuditService
{
    public function record(
        AssessmentActorContext $context,
        string $action,
        string $outcome,
        string $reasonCode,
        ?Model $target = null,
        array $metadata = [],
    ): AuditEvent {
        return AuditEvent::query()->create([
            'event_type' => 'assessment.'.$action,
            'actor_user_id' => $context->actor->id,
            'school_id' => $context->school->id,
            'affected_resource_type' => $target !== null ? $target::class : null,
            'affected_resource_id' => $target?->getAttribute('uuid'),
            'outcome' => $outcome,
            'tenant_safe_metadata' => $this->redact($metadata + [
                'authority' => $context->authority,
                'correlation_id' => $context->correlationId,
                'reason_code' => $reasonCode,
            ]),
            'occurred_at' => now(),
        ]);
    }

    public function recordRequestFailure(Request $request, string $action, string $outcome, string $reasonCode): ?AuditEvent
    {
        if (! $this->isAssessmentRequest($request)) {
            return null;
        }

        $tenantContext = $request->attributes->get('tenant_context');
        $actor = $request->attributes->get('auth_user');

        return AuditEvent::query()->create([
            'event_type' => 'assessment.'.$action,
            'actor_user_id' => $actor?->id,
            'school_id' => $tenantContext instanceof TenantContext ? $tenantContext->school?->id : null,
            'affected_resource_type' => null,
            'affected_resource_id' => null,
            'outcome' => $outcome,
            'tenant_safe_metadata' => [
                'reason_code' => $reasonCode,
                'route' => $request->path(),
            ],
            'occurred_at' => now(),
        ]);
    }

    private function redact(array $metadata): array
    {
        unset(
            $metadata['answer_text'],
            $metadata['file_contents'],
            $metadata['storage_path'],
            $metadata['credentials'],
            $metadata['answer_key'],
            $metadata['feedback_summary'],
            $metadata['private_grading_note'],
            $metadata['payload'],
        );

        return $metadata;
    }

    private function isAssessmentRequest(Request $request): bool
    {
        return str_contains($request->path(), 'questionnaires')
            || str_contains($request->path(), 'questionnaire-responses');
    }
}
