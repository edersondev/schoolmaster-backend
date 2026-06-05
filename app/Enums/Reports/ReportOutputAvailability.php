<?php

declare(strict_types=1);

namespace App\Enums\Reports;

enum ReportOutputAvailability: string
{
    case Pending = 'pending';
    case Available = 'available';
    case Failed = 'failed';
    case Expired = 'expired';
    case Unsupported = 'unsupported';
}
