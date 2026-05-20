<?php

declare(strict_types=1);

namespace App\Http\Requests\LearningSets;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateLearningSetRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_type' => ['required', 'string', Rule::in(['content_item', 'questionnaire'])],
            'entries.*.entry_reference_id' => ['required', 'uuid'],
            'entries.*.sequence' => ['required', 'integer', 'min:1', 'distinct'],
            'student_profile_ids' => ['required', 'array', 'min:1'],
            'student_profile_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
