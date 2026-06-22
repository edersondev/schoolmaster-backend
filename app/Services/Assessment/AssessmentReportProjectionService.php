<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Models\AssessmentResponseAttempt;
use App\Models\ReportRun;

final class AssessmentReportProjectionService
{
    public function contentsFor(ReportRun $run): string
    {
        $count = AssessmentResponseAttempt::query()
            ->where('school_id', $run->school_id)
            ->count();

        return 'ADVANCED_ASSESSMENTS report '.$run->uuid.' response_count='.$count;
    }
}
