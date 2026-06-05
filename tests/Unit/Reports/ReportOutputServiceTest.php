<?php

declare(strict_types=1);

namespace Tests\Unit\Reports;

use App\Services\Reports\ReportOutputService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReportOutputServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_output_service_rejects_unsupported_format_for_domain(): void
    {
        $this->expectException(ValidationException::class);

        app(ReportOutputService::class)->assertFormatsSupported('school_activity', ['xlsx']);
    }
}
