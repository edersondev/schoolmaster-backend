<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserLifecycleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore((string) $this->route('userId'), 'uuid'),
            ],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'role_ids' => ['sometimes', 'array', 'min:1'],
            'role_ids.*' => ['string', 'uuid'],
        ];
    }
}
