<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportRun;

final class ReportOutputGenerationService
{
    public function contentsFor(ReportRun $run, string $format): string
    {
        return strtoupper($format).' report '.$run->uuid;
    }
}
