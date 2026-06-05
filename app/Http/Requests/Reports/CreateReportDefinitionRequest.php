<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

class CreateReportDefinitionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'domain' => ['required', 'string', Rule::in(['attendance', 'grades', 'academic_structure', 'school_activity'])],
            'fields' => ['required', 'array', 'min:1', 'max:25'],
            'fields.*' => ['required', 'string'],
            'filters' => ['sometimes', 'array', 'max:10'],
            'grouping' => ['sometimes', 'array', 'max:2'],
            'grouping.*' => ['required', 'string'],
            'sorting' => ['sometimes', 'array', 'max:3'],
            'output_formats' => ['required', 'array', 'min:1'],
            'output_formats.*' => ['required', 'string', Rule::in(['pdf', 'csv', 'xlsx'])],
        ];
    }
}
