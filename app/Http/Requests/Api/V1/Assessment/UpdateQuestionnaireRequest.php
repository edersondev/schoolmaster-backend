<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

final class UpdateQuestionnaireRequest extends StoreQuestionnaireRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['title'] = ['sometimes', 'string', 'max:255'];
        $rules['questions'] = ['sometimes', 'array', 'min:1'];
        $rules['questions.*.question_type'] = ['required_with:questions', ...array_slice($rules['questions.*.question_type'], 1)];
        $rules['questions.*.prompt'] = ['required_with:questions', 'string', 'max:2000'];
        $rules['questions.*.sequence'] = ['required_with:questions', 'integer', 'min:1', 'distinct'];

        return $rules;
    }
}
