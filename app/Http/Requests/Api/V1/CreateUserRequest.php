<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;

final class CreateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'uuid'],
        ];
    }
}
