<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherWorkflow;

use App\Http\Requests\ApiFormRequest;
use App\Http\Requests\TeacherWorkflow\Concerns\ValidatesTeacherWorkflowRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TeacherMaterialsRequest extends ApiFormRequest
{
    use ValidatesTeacherWorkflowRequests;

    public function rules(): array
    {
        if ($this->isStatusRoute()) {
            return [
                'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ];
        }

        if ($this->isMethod('PATCH')) {
            if ($this->is('api/v1/questionnaires/*')) {
                return [
                    'title' => ['sometimes', 'string', 'max:255'],
                    'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
                    'questions' => ['sometimes', 'array', 'min:1'],
                    'questions.*.question_type' => ['required_with:questions', 'string', Rule::in(['multiple_choice', 'true_false', 'short_text'])],
                    'questions.*.prompt' => ['required_with:questions', 'string', 'max:2000'],
                    'questions.*.options' => ['sometimes', 'array', 'min:2'],
                    'questions.*.options.*' => ['string', 'max:500'],
                    'questions.*.correct_answer' => ['sometimes', 'nullable', 'string', 'max:1000'],
                    'questions.*.sequence' => ['required_with:questions', 'integer', 'min:1'],
                ];
            }

            return [
                'folder_id' => ['sometimes', 'nullable', 'uuid'],
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            ];
        }

        return [];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $this->validateTeacherWorkflowRequest($validator, ['active', 'inactive']);

        $validator->after(function (Validator $validator): void {
            if (! $this->isMethod('PATCH') || $this->isStatusRoute()) {
                return;
            }

            if ($this->validatedPayloadIsEmpty()) {
                $validator->errors()->add('payload', 'At least one documented field must be provided.');
            }

            $questions = $this->input('questions');
            if (! is_array($questions)) {
                return;
            }

            $sequences = [];
            foreach ($questions as $index => $question) {
                if (! is_array($question)) {
                    continue;
                }

                $extra = array_diff(array_keys($question), ['question_type', 'prompt', 'options', 'correct_answer', 'sequence']);
                if ($extra !== []) {
                    $validator->errors()->add("questions.$index", 'Question contains undocumented fields.');
                }

                if (($question['question_type'] ?? null) === 'multiple_choice' && count($question['options'] ?? []) < 2) {
                    $validator->errors()->add("questions.$index.options", 'Multiple choice questions require at least two options.');
                }

                if (isset($question['sequence']) && in_array($question['sequence'], $sequences, true)) {
                    $validator->errors()->add('questions', 'Question sequences must be unique.');
                }

                if (isset($question['sequence'])) {
                    $sequences[] = $question['sequence'];
                }
            }
        });
    }

    private function isStatusRoute(): bool
    {
        return str_ends_with($this->path(), '/status');
    }

    private function validatedPayloadIsEmpty(): bool
    {
        return $this->all() === [];
    }
}
