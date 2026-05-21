<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\TenantContext;
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
    ) {}

    public function resolveDownload(User $actor, TenantContext $context, string $reportRunUuid, array $query): ReportOutput
    {
        $format = $this->filters->validateFormat($query);
        $school = $this->tenantContext->requireSchool($context);
        $this->assertReportPermission($actor, $school, 'reports.view');

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

        if ($output->isExpired()) {
            throw new OutputExpiredException('Generated report output file has expired; request a new ReportRun.');
        }

        return $output;
    }
}
