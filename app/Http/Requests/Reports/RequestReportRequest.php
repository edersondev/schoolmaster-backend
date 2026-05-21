<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class RequestReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(['attendance', 'grades', 'academic_structure', 'school_activity'])],
            'filters' => ['required', 'array'],
            'filters.academic_period_id' => ['required', 'string', 'uuid'],
            'filters.student_profile_id' => ['sometimes', 'string', 'uuid'],
            'filters.user_id' => ['sometimes', 'string', 'uuid'],
            'filters.status' => ['sometimes', 'string'],
            'filters.start_date' => ['sometimes', 'date'],
            'filters.end_date' => ['sometimes', 'date', 'after_or_equal:filters.start_date'],
        ];
    }
}
