<?php

declare(strict_types=1);

namespace App\Http\Requests\TeacherContent;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateTeacherContentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'folder_id' => ['nullable', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', Rule::in(['pdf', 'image', 'text', 'office_document'])],
            'file' => ['required', 'file', 'max:25600'],
        ];
    }
}
