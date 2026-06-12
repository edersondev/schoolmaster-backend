<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class SubmitStudentQuestionnaireResponseRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'questionnaire_id' => ['required', 'uuid'],
            'learning_set_id' => ['required', 'uuid'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'uuid', 'distinct'],
            'answers.*.question_type' => ['required', 'string', Rule::in(['multiple_choice', 'true_false', 'short_text', 'long_text', 'file_response'])],
            'answers.*.answer_text' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'answers.*.file' => ['sometimes', 'file', 'max:25600'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            foreach (($this->all()['answers'] ?? []) as $index => $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $unsupported = array_diff(array_keys($answer), ['question_id', 'question_type', 'answer_text', 'file']);

                foreach ($unsupported as $field) {
                    $validator->errors()->add("answers.$index.$field", 'This field is not documented for this request.');
                }
            }
        });
    }
}
