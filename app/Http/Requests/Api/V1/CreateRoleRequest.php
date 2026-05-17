<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', 'uuid'],
            'scope' => ['required', Rule::in(['platform', 'school'])],
            'name' => ['required', 'string', 'max:255'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'uuid'],
        ];
    }
}
