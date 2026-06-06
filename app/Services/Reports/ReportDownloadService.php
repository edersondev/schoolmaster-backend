<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\TenantContext;
use App\DTOs\Reports\ReportActorContext;
use App\Exceptions\OutputExpiredException;
use App\Models\ReportOutput;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Concerns\AuthorizesSchoolReports;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ReportDownloadService
{
    use AuthorizesSchoolReports;

    public function __construct(
        private readonly TenantContextService $tenantContext,
        private readonly ReportFilterValidator $filters,
        private readonly ReportAuditService $audit,
    ) {}

    public function resolveDownload(User $actor, TenantContext $context, string $reportRunUuid, array $query): ReportOutput
    {
        $format = $this->filters->validateFormat($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertReportPermission($actor, $school, 'reports.view');
        $actorContext = new ReportActorContext($actor, $school, (string) \Illuminate\Support\Str::uuid());

        $run = ReportRun::query()
            ->where('uuid', $reportRunUuid)
            ->where('school_id', $school->id)
            ->where('status', 'generated')
            ->first();

        if ($run === null) {
            throw (new ModelNotFoundException)->setModel(ReportRun::class);
        }

        $output = ReportOutput::query()
            ->where('report_run_id', $run->id)
            ->where('school_id', $school->id)
            ->where('format', $format)
            ->first();

        if ($output === null) {
            throw (new ModelNotFoundException)->setModel(ReportOutput::class);
        }

        if ($output->isExpired() || $output->availability?->value === 'expired') {
            $this->audit->record($actorContext, 'output_expired', 'rejected', 'report_output', $output->id, 'output_expired', reportRun: $run);
            throw new OutputExpiredException('Generated report output file has expired; request a new ReportRun.');
        }

        $this->audit->record($actorContext, 'downloaded', 'succeeded', 'report_output', $output->id, 'report_downloaded', reportRun: $run);

        return $output;
    }
}
