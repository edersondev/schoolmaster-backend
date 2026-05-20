<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportOutput;
use App\Models\ReportRun;
use Illuminate\Support\Facades\Storage;

final class ReportOutputWriter
{
    public function __construct(private readonly ReportOutputAvailability $availability) {}

    public function write(ReportRun $run, string $format, string $contents): ReportOutput
    {
        $generatedAt = now();
        $path = $run->school->uuid.'/'.$run->uuid.'/report.'.$format;

        Storage::disk('report_outputs')->put($path, $contents, ['visibility' => 'private']);

        return ReportOutput::query()->updateOrCreate(
            ['report_run_id' => $run->id, 'format' => $format],
            [
                'school_id' => $run->school_id,
                'storage_path' => $path,
                'generated_at' => $generatedAt,
                'expires_at' => $this->availability->expiresAt($generatedAt),
                'status' => 'available',
            ],
        );
    }
}
