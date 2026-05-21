<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ReportRun;
use App\Services\Reports\ReportOutputAvailability;
use App\Services\Reports\ReportOutputWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateReportRunOutputs implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $reportRunId) {}

    public function handle(): void
    {
        $run = ReportRun::query()->with('school')->find($this->reportRunId);

        if ($run === null || $run->status !== 'requested') {
            return;
        }

        $writer = new ReportOutputWriter(new ReportOutputAvailability);

        foreach (['pdf', 'csv'] as $format) {
            $writer->write($run, $format, strtoupper($format).' report '.$run->uuid);
        }

        $generatedAt = now();
        $run->update([
            'status' => 'generated',
            'generated_at' => $generatedAt,
            'output_expires_at' => $generatedAt->copy()->addDays(90),
            'outputs_available' => true,
        ]);
    }
}
