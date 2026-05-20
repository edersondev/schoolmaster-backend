<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportOutput;
use Illuminate\Support\Facades\Storage;

final class ReportOutputRetentionService
{
    public function expireOutputs(): int
    {
        $expired = ReportOutput::query()
            ->where('status', 'available')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $output) {
            Storage::disk('report_outputs')->delete($output->storage_path);
            $output->update(['status' => 'expired']);
            $output->reportRun()->update(['outputs_available' => false]);
        }

        return $expired->count();
    }
}
