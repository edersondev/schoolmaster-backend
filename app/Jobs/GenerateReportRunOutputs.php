<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ReportRun;
use App\Services\Reports\ReportLifecycleService;
use App\Services\Reports\ReportOutputGenerationService;
use App\Services\Reports\ReportOutputAvailability;
use App\Services\Reports\ReportOutputWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateReportRunOutputs implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $reportRunId) {}

    public function handle(ReportLifecycleService $lifecycle, ReportOutputGenerationService $generator): void
    {
        $run = ReportRun::query()->with('school')->find($this->reportRunId);

        if ($run === null || ! in_array($run->status, ['requested', 'generating'], true)) {
            return;
        }

        $writer = new ReportOutputWriter(new ReportOutputAvailability);

        foreach ($run->output_formats as $format) {
            $writer->write($run, $format, $generator->contentsFor($run, $format));
        }

        $lifecycle->completeGeneration($run);
    }
}
