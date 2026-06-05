<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

final class UpdateReportDefinitionRequest extends CreateReportDefinitionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_map(fn (string $rule): string => $rule === 'required' ? 'sometimes' : $rule, $fieldRules);
        }

        return $rules;
    }
}
