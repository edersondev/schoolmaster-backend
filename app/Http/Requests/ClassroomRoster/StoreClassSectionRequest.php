<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class StoreClassSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'min:1', 'max:80'],
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'course' => ['sometimes', 'array'],
            'course.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'course.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'classroom' => ['sometimes', 'array'],
            'classroom.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'classroom.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'section' => ['sometimes', 'array'],
            'section.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'section.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'group' => ['sometimes', 'array'],
            'group.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'group.name' => ['sometimes', 'string', 'min:1', 'max:255'],
        ];
    }
}
