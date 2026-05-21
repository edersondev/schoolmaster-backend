<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Reports\ReportOutputAvailability;
use Tests\TestCase;

final class ReportOutputAvailabilityTest extends TestCase
{
    public function test_report_output_expiry_is_90_days_after_generation(): void
    {
        $generatedAt = now();

        $this->assertTrue((new ReportOutputAvailability)->expiresAt($generatedAt)->equalTo($generatedAt->copy()->addDays(90)));
    }
}
