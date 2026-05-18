<?php

declare(strict_types=1);

namespace App\Http\Requests\Questionnaires;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateQuestionnaireRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_type' => ['required', 'string', Rule::in(['multiple_choice', 'true_false', 'short_text'])],
            'questions.*.prompt' => ['required', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['required_with:questions.*.options', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.sequence' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
