<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportActorContext;
use App\Models\ReportDefinition;
use App\Models\ReportLifecycleEvent;
use App\Models\ReportRun;

final class ReportAuditService
{
    public function record(
        ReportActorContext $context,
        string $action,
        string $outcome,
        string $targetType,
        ?int $targetId,
        string $reasonCode,
        array $summary = [],
        ?ReportRun $reportRun = null,
        ?ReportDefinition $reportDefinition = null,
    ): ReportLifecycleEvent {
        return ReportLifecycleEvent::query()->create([
            'school_id' => $context->school->id,
            'actor_user_id' => $context->actor->id,
            'report_run_id' => $reportRun?->id,
            'report_definition_id' => $reportDefinition?->id,
            'action' => $action,
            'outcome' => $outcome,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'correlation_id' => $context->correlationId,
            'reason_code' => $reasonCode,
            'summary' => $summary,
            'occurred_at' => now(),
        ]);
    }
}
