<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class UpdateClassSectionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'course' => ['sometimes', 'nullable', 'array'],
            'course.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'course.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'classroom' => ['sometimes', 'nullable', 'array'],
            'classroom.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'classroom.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'section' => ['sometimes', 'nullable', 'array'],
            'section.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'section.name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'group' => ['sometimes', 'nullable', 'array'],
            'group.code' => ['sometimes', 'string', 'min:1', 'max:80'],
            'group.name' => ['sometimes', 'string', 'min:1', 'max:255'],
        ];
    }
}
