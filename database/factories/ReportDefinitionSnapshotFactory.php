<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportDefinitionSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinitionSnapshot>
 */
final class ReportDefinitionSnapshotFactory extends Factory
{
    protected $model = ReportDefinitionSnapshot::class;

    public function definition(): array
    {
        $definition = ReportDefinition::factory()->create();

        return [
            'school_id' => $definition->school_id,
            'report_definition_id' => $definition->id,
            'definition_version' => $definition->version,
            'domain' => $definition->domain,
            'fields' => $definition->fields,
            'filters' => $definition->filters,
            'grouping' => $definition->grouping,
            'sorting' => $definition->sorting,
            'output_formats' => $definition->output_formats,
            'runtime_filters' => ['academic_period_id' => fake()->uuid()],
        ];
    }
}
