<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\RequestReportData;
use App\DTOs\TenantContext;
use App\Jobs\GenerateReportRunOutputs;
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
    ) {}

    public function request(User $actor, TenantContext $context, RequestReportData $data): ReportRun
    {
        $school = $this->tenantContext->requireSchool($context);
        $this->assertReportPermission($actor, $school, 'reports.request');
        $validated = $this->filters->validateRequest([
            'report_type' => $data->reportType,
            'filters' => $data->filters,
        ], $school->id);

        $run = ReportRun::query()->create([
            'school_id' => $school->id,
            'requested_by_user_id' => $actor->id,
            'report_type' => $validated['report_type'],
            'filter_summary' => $validated['filters'],
            'output_formats' => ['pdf', 'csv'],
            'status' => 'requested',
            'outputs_available' => false,
        ])->load(['school', 'requester']);

        GenerateReportRunOutputs::dispatch($run->id)->onQueue(config('queue.report_queue'));

        return $run;
    }
}
