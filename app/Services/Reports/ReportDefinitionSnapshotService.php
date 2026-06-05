<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ReportDefinition;
use App\Models\ReportDefinitionSnapshot;

final class ReportDefinitionSnapshotService
{
    public function create(ReportDefinition $definition, array $runtimeFilters = []): ReportDefinitionSnapshot
    {
        return ReportDefinitionSnapshot::query()->create([
            'school_id' => $definition->school_id,
            'report_definition_id' => $definition->id,
            'definition_version' => $definition->version,
            'domain' => $definition->domain,
            'fields' => $definition->fields,
            'filters' => $definition->filters,
            'grouping' => $definition->grouping,
            'sorting' => $definition->sorting,
            'output_formats' => $definition->output_formats,
            'runtime_filters' => $runtimeFilters,
        ]);
    }
}
