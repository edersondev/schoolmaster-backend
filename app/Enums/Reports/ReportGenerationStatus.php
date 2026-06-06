<?php

declare(strict_types=1);

namespace App\Enums\Reports;

enum ReportGenerationStatus: string
{
    case Requested = 'requested';
    case Generating = 'generating';
    case Generated = 'generated';
    case Failed = 'failed';
    case Canceled = 'canceled';
}
