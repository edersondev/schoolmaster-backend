<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

use App\Http\Requests\ApiFormRequest;
use App\Services\Assessment\AssessmentQuestionSchemaService;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_type' => ['required', 'string', Rule::in(AssessmentQuestionSchemaService::QUESTION_TYPES)],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['nullable', 'array', 'min:2'],
            'questions.*.options.*' => ['required_with:questions.*.options', 'string', 'max:500'],
            'questions.*.correct_answer' => ['nullable', 'string', 'max:1000'],
            'questions.*.answer_schema' => ['nullable', 'array'],
            'questions.*.answer_schema.min_length' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'questions.*.answer_schema.max_length' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'questions.*.answer_schema.allowed_file_categories' => ['nullable', 'array'],
            'questions.*.answer_schema.allowed_file_categories.*' => ['string', Rule::in(['pdf', 'image', 'text', 'office'])],
            'questions.*.answer_schema.max_file_size_bytes' => ['nullable', 'integer', 'size:26214400'],
            'questions.*.answer_schema.max_files' => ['nullable', 'integer', 'size:1'],
            'questions.*.grading_rule' => ['nullable', 'array'],
            'questions.*.grading_rule.mode' => ['nullable', 'string', Rule::in(['auto', 'manual_0_100'])],
            'questions.*.grading_rule.allow_exempt' => ['nullable', 'boolean'],
            'questions.*.visibility' => ['nullable', 'array'],
            'questions.*.visibility.student_answer_visible' => ['nullable', 'boolean'],
            'questions.*.visibility.report_visibility' => ['nullable', 'string', Rule::in(['summary_only'])],
            'questions.*.sequence' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
