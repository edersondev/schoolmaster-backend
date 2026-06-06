<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportActorContext;
use App\DTOs\Reports\ReportLifecycleActionData;
use App\DTOs\TenantContext;
use App\Jobs\GenerateReportRunOutputs;
use App\Exceptions\ConflictException;
use App\Exceptions\PermissionDeniedException;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class ReportLifecycleService
{
    public function __construct(
        private readonly ReportTenantContextService $tenantContext,
        private readonly ReportAuditService $audit,
        private readonly ReportOutputService $outputs,
    ) {}

    public function retry(User $actor, TenantContext $context, string $reportRunUuid, ReportLifecycleActionData $data): ReportRun
    {
        return DB::transaction(function () use ($actor, $context, $reportRunUuid, $data): ReportRun {
            [$run, $actorContext] = $this->resolveRun($actor, $context, $reportRunUuid, 'retry');

            if (! $this->isRetryable($run)) {
                $this->auditConflict($actorContext, $run, 'retry_rejected');
                throw new ConflictException('Report run is not eligible for retry.');
            }

            $retry = ReportRun::query()->create([
                'school_id' => $run->school_id,
                'requested_by_user_id' => $actor->id,
                'report_type' => $run->report_type,
                'filter_summary' => $run->filter_summary,
                'output_formats' => $run->output_formats,
                'status' => 'requested',
                'generation_status' => 'requested',
                'outputs_available' => false,
                'source_report_run_id' => $run->id,
                'report_definition_id' => $run->report_definition_id,
                'report_definition_snapshot_id' => $run->report_definition_snapshot_id,
                'correlation_id' => $actorContext->correlationId,
            ]);

            $this->outputs->createPendingOutputs($retry);
            $run->update(['superseded_by_report_run_id' => $retry->id]);
            GenerateReportRunOutputs::dispatch($retry->id)->onQueue(config('queue.report_queue'))->afterCommit();

            $this->audit->record($actorContext, 'retry_requested', 'succeeded', 'report_run', $run->id, $data->reasonCode ?: 'retry_failed_generation', reportRun: $run);

            return $retry->load(['school', 'requester', 'outputs', 'sourceReportRun', 'supersededByReportRun']);
        });
    }

    public function cancel(User $actor, TenantContext $context, string $reportRunUuid, ReportLifecycleActionData $data): ReportRun
    {
        return DB::transaction(function () use ($actor, $context, $reportRunUuid, $data): ReportRun {
            [$run, $actorContext] = $this->resolveRun($actor, $context, $reportRunUuid, 'cancel');

            if (! in_array($this->statusValue($run), ['requested', 'generating'], true) || $run->outputs()->where('availability', 'available')->exists()) {
                $this->auditConflict($actorContext, $run, 'cancel_rejected');
                throw new ConflictException('Report run is not eligible for cancellation.');
            }

            $run->update([
                'status' => 'canceled',
                'generation_status' => 'canceled',
                'cancellation_reason_code' => $data->reasonCode,
            ]);

            $this->audit->record($actorContext, 'canceled', 'succeeded', 'report_run', $run->id, $data->reasonCode, reportRun: $run);

            return $run->refresh()->load(['school', 'requester', 'outputs', 'sourceReportRun', 'supersededByReportRun']);
        });
    }

    public function delete(User $actor, TenantContext $context, string $reportRunUuid): ReportRun
    {
        return DB::transaction(function () use ($actor, $context, $reportRunUuid): ReportRun {
            [$run, $actorContext] = $this->resolveRun($actor, $context, $reportRunUuid, 'delete');

            if ($run->trashed()) {
                $this->auditConflict($actorContext, $run, 'delete_rejected');
                throw new ConflictException('Report run is already deleted.');
            }

            $run->delete();
            $this->audit->record($actorContext, 'deleted', 'succeeded', 'report_run', $run->id, 'report_run_deleted', reportRun: $run);

            return $run->refresh()->load(['school', 'requester', 'outputs', 'sourceReportRun', 'supersededByReportRun']);
        }, attempts: 1);
    }

    public function restore(User $actor, TenantContext $context, string $reportRunUuid): ReportRun
    {
        return DB::transaction(function () use ($actor, $context, $reportRunUuid): ReportRun {
            [$run, $actorContext] = $this->resolveRun($actor, $context, $reportRunUuid, 'restore', withTrashed: true);

            if (! $run->trashed()) {
                $this->auditConflict($actorContext, $run, 'restore_rejected');
                throw new ConflictException('Only deleted report runs can be restored.');
            }

            $run->restore();
            $this->audit->record($actorContext, 'restored', 'succeeded', 'report_run', $run->id, 'report_run_restored', reportRun: $run);

            return $run->refresh()->load(['school', 'requester', 'outputs', 'sourceReportRun', 'supersededByReportRun']);
        }, attempts: 1);
    }

    public function completeGeneration(ReportRun $run): bool
    {
        $run->refresh();

        if (! in_array($this->statusValue($run), ['requested', 'generating'], true)) {
            return false;
        }

        $run->update([
            'status' => 'generated',
            'generation_status' => 'generated',
            'generated_at' => now(),
            'output_expires_at' => $this->outputs->resolveRunExpiry($run),
            'outputs_available' => true,
        ]);

        return true;
    }

    private function resolveRun(User $actor, TenantContext $context, string $reportRunUuid, string $action, bool $withTrashed = false): array
    {
        $actorContext = $this->tenantContext->resolve($actor, $context);

        $query = ReportRun::query()
            ->where('uuid', $reportRunUuid)
            ->where('school_id', $actorContext->school->id)
            ->lockForUpdate();

        if ($withTrashed) {
            $query->withTrashed();
        }

        $run = $query->first();

        if ($run === null) {
            throw (new ModelNotFoundException)->setModel(ReportRun::class);
        }

        if (! $actor->hasSchoolPermission('reports.lifecycle', $actorContext->school->id)) {
            $this->audit->record($actorContext, 'denied', 'denied', 'report_run', $run->id, 'access_denied', reportRun: $run);
            throw new PermissionDeniedException('The authenticated user lacks permission for this action.');
        }

        return [$run, $actorContext];
    }

    private function isRetryable(ReportRun $run): bool
    {
        if ($run->trashed() || $run->superseded_by_report_run_id !== null) {
            return false;
        }

        if ($this->statusValue($run) === 'failed') {
            return true;
        }

        return $this->statusValue($run) === 'generated'
            && $run->outputs()
                ->where(function ($query): void {
                    $query->where('availability', 'expired')
                        ->orWhere('expires_at', '<=', now());
                })
                ->exists();
    }

    private function statusValue(ReportRun $run): string
    {
        $status = $run->generation_status;

        return is_object($status) && property_exists($status, 'value') ? $status->value : (string) ($status ?? $run->status);
    }

    private function auditConflict(ReportActorContext $context, ReportRun $run, string $reasonCode): void
    {
        $this->audit->record($context, 'conflict', 'conflicted', 'report_run', $run->id, $reasonCode, reportRun: $run);
    }
}
