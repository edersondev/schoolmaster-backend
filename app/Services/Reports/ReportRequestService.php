<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\RequestReportData;
use App\DTOs\Reports\ReportActorContext;
use App\DTOs\TenantContext;
use App\Jobs\GenerateReportRunOutputs;
use App\Enums\Reports\ReportDefinitionState;
use App\Exceptions\ConflictException;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolReports;
use App\Services\TenantContextService;

final class ReportRequestService
{
    use AuthorizesSchoolReports;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly ReportFilterValidator $filters,
        private readonly ReportDefinitionSnapshotService $snapshots,
        private readonly ReportAuditService $audit,
        private readonly ReportOutputService $outputs,
    ) {}

    public function request(User $actor, TenantContext $context, RequestReportData $data): ReportRun
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertReportPermission($actor, $school, 'reports.request');
        $definition = null;
        $snapshot = null;

        if (($data->reportDefinitionId ?? null) !== null) {
            $definition = ReportDefinition::query()
                ->where('uuid', $data->reportDefinitionId)
                ->where('school_id', $school->id)
                ->first();

            if ($definition === null) {
                throw (new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel(ReportDefinition::class);
            }

            if ($definition->lifecycle_state !== ReportDefinitionState::Active) {
                throw new ConflictException('Only active report definitions can be requested.');
            }

            $allowedRuntimeFilters = collect($definition->filters)
                ->map(fn (mixed $filter): ?string => is_array($filter) ? ($filter['field'] ?? $filter['id'] ?? null) : null)
                ->filter(fn (mixed $filter): bool => is_string($filter))
                ->values()
                ->all();

            $validated = [
                'report_type' => $definition->domain,
                'filters' => $this->filters->validateRuntimeFilters($data->filters, $school->id, $allowedRuntimeFilters),
            ];
            $this->outputs->assertFormatsSupported($definition->domain, $data->outputFormats, $definition->output_formats);
            $snapshot = $this->snapshots->create($definition, $validated['filters']);
        } else {
            $validated = $this->filters->validateRequest([
                'report_type' => $data->reportType,
                'filters' => $data->filters,
            ], $school->id);
            $this->outputs->assertFormatsSupported($validated['report_type'], $data->outputFormats ?: ['pdf', 'csv']);
        }

        $run = ReportRun::query()->create([
            'school_id' => $school->id,
            'requested_by_user_id' => $actor->id,
            'report_type' => $validated['report_type'],
            'filter_summary' => $validated['filters'],
            'output_formats' => $data->outputFormats ?: ['pdf', 'csv'],
            'status' => 'requested',
            'generation_status' => 'requested',
            'outputs_available' => false,
            'report_definition_id' => $definition?->id,
            'report_definition_snapshot_id' => $snapshot?->id,
        ])->load(['school', 'requester', 'reportDefinition', 'reportDefinitionSnapshot']);

        $this->outputs->createPendingOutputs($run);

        GenerateReportRunOutputs::dispatch($run->id)->onQueue(config('queue.report_queue'));

        if ($definition !== null) {
            $this->audit->record(
                new ReportActorContext($actor, $school, (string) $run->correlation_id),
                'requested',
                'succeeded',
                'report_run',
                $run->id,
                'custom_report_requested',
                reportRun: $run,
                reportDefinition: $definition,
            );
        }

        return $run;
    }
}
