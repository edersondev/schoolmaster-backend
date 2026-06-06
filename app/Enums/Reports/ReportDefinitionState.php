<?php

declare(strict_types=1);

namespace App\Enums\Reports;

enum ReportDefinitionState: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Deleted = 'deleted';
}
