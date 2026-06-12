<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class ListQuestionnaireResponsesRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'questionnaire_id' => ['sometimes', 'uuid'],
            'learning_set_id' => ['sometimes', 'uuid'],
            'grading_status' => ['sometimes', 'string', Rule::in(['unsubmitted', 'submitted', 'scan_blocked', 'scan_failed', 'needs_review', 'partially_graded', 'graded', 'returned', 'exempted'])],
        ];
    }
}
