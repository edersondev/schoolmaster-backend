<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class GradeAssessmentResponseRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grading_outcomes' => ['required', 'array', 'min:1'],
            'grading_outcomes.*.answer_id' => ['required', 'uuid', 'distinct'],
            'grading_outcomes.*.status' => ['required', 'string', Rule::in(['graded', 'returned', 'exempted'])],
            'grading_outcomes.*.score' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'grading_outcomes.*.feedback_summary' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'grading_outcomes.*.private_grading_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
