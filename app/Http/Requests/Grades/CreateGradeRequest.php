<?php

declare(strict_types=1);

namespace App\Http\Requests\Grades;

use App\Http\Requests\ApiFormRequest;

final class CreateGradeRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'uuid'],
            'academic_period_id' => ['required', 'uuid'],
            'grade_value' => ['required', 'numeric', 'min:0', 'max:100'],
            'grade_label' => ['nullable', 'string', 'max:64'],
        ];
    }
}
