<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportRun;
use App\Services\Assessment\AssessmentReportProjectionService;

final class ReportOutputGenerationService
{
    public function __construct(private readonly AssessmentReportProjectionService $assessmentProjection) {}

    public function contentsFor(ReportRun $run, string $format): string
    {
        if ($run->report_type === 'advanced_assessments') {
            return $this->assessmentProjection->contentsFor($run);
        }

        return strtoupper($format).' report '.$run->uuid;
    }
}
