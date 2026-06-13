<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportOutput;
use App\Models\ReportRun;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class ReportOutputService
{
    private const SUPPORTED_FORMATS = [
        'attendance' => ['pdf', 'csv', 'xlsx'],
        'grades' => ['pdf', 'csv', 'xlsx'],
        'academic_structure' => ['pdf', 'csv'],
        'school_activity' => ['pdf', 'csv'],
        'advanced_assessments' => ['pdf', 'csv'],
    ];

    public function assertFormatsSupported(string $reportType, array $formats, ?array $definitionFormats = null): void
    {
        $supported = $definitionFormats ?? self::SUPPORTED_FORMATS[$reportType] ?? ['pdf', 'csv'];

        foreach ($formats as $index => $format) {
            if (! in_array($format, $supported, true)) {
                throw ValidationException::withMessages(["output_formats.$index" => ['The requested output format is not supported for this report.']]);
            }
        }
    }

    public function createPendingOutputs(ReportRun $run): void
    {
        foreach ($run->output_formats as $format) {
            ReportOutput::query()->firstOrCreate([
                'report_run_id' => $run->id,
                'format' => $format,
            ], [
                'school_id' => $run->school_id,
                'storage_path' => '',
                'generated_at' => now(),
                'expires_at' => now()->addDays(90),
                'status' => 'pending',
                'availability' => 'pending',
            ]);
        }
    }

    public function markExpiredOutputs(): int
    {
        return ReportOutput::query()
            ->where('availability', 'available')
            ->where('expires_at', '<=', now())
            ->update(['availability' => 'expired']);
    }

    public function resolveRunExpiry(ReportRun $run): ?CarbonInterface
    {
        $expiry = $run->outputs()->max('expires_at');

        return $expiry === null ? null : now()->parse($expiry);
    }
}
