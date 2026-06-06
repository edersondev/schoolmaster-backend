<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportDefinitionData;
use App\Enums\Reports\ReportDefinitionState;
use App\Exceptions\ConflictException;
use App\Models\ReportDefinition;
use Illuminate\Validation\ValidationException;

final class ReportDefinitionValidationService
{
    public function validateCatalog(ReportDefinitionData $data, array $catalog): void
    {
        $domains = collect($catalog['domains']);
        $domain = $domains->firstWhere('id', $data->domain);

        if ($domain === null) {
            throw ValidationException::withMessages(['domain' => ['The selected report domain is not supported.']]);
        }

        $fieldIds = collect($domain['fields'])->pluck('id')->all();
        $filterIds = collect($domain['filters'])->pluck('id')->all();

        $this->assertAllowed('fields', $data->fields, $fieldIds);
        $this->assertAllowed('grouping', $data->grouping, $domain['grouping']);
        $this->assertAllowed('output_formats', $data->outputFormats, $domain['output_formats']);

        foreach ($data->filters as $index => $filter) {
            $field = is_array($filter) ? ($filter['field'] ?? $filter['id'] ?? null) : null;

            if (! is_string($field) || ! in_array($field, $filterIds, true)) {
                throw ValidationException::withMessages(["filters.$index" => ['The selected filter is not supported by the report catalog.']]);
            }
        }

        foreach ($data->sorting as $index => $sort) {
            $field = is_array($sort) ? ($sort['field'] ?? null) : null;

            if (! is_string($field) || ! in_array($field, $fieldIds, true)) {
                throw ValidationException::withMessages(["sorting.$index" => ['The selected sort field is not supported by the report catalog.']]);
            }
        }
    }

    public function assertCanUpdate(ReportDefinition $definition, array $payload): void
    {
        if ($definition->lifecycle_state !== ReportDefinitionState::Active) {
            return;
        }

        $structuralFields = ['domain', 'fields', 'filters', 'grouping', 'sorting', 'output_formats'];

        foreach ($structuralFields as $field) {
            if (array_key_exists($field, $payload)) {
                throw new ConflictException('Active report definitions allow only name and description updates.');
            }
        }
    }

    private function assertAllowed(string $field, array $values, array $allowed): void
    {
        foreach ($values as $index => $value) {
            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                throw ValidationException::withMessages(["$field.$index" => ['The selected value is not supported by the report catalog.']]);
            }
        }
    }
}
