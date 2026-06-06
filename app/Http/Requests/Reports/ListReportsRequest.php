<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class ListReportsRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('include_deleted')) {
            return;
        }

        $this->merge([
            'include_deleted' => filter_var(
                $this->input('include_deleted'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'report_type' => ['sometimes', 'string', Rule::in(['attendance', 'grades', 'academic_structure', 'school_activity'])],
            'generation_status' => ['sometimes', 'string', Rule::in(['requested', 'generating', 'generated', 'failed', 'canceled'])],
            'report_source' => ['sometimes', 'string', Rule::in(['built_in', 'custom'])],
            'include_deleted' => ['sometimes', 'boolean'],
        ];
    }
}
