<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportOutput;
use Carbon\CarbonInterface;

final class ReportOutputAvailability
{
    public function expiresAt(CarbonInterface $generatedAt): CarbonInterface
    {
        return $generatedAt->copy()->addDays(90);
    }

    public function isAvailable(ReportOutput $output): bool
    {
        return $output->status === 'available' && $output->expires_at->isFuture();
    }
}
