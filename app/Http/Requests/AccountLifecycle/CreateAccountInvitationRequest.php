<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountLifecycle;

use App\Http\Requests\ApiFormRequest;

final class CreateAccountInvitationRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', 'in:platform,school'],
            'school_id' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'uuid'],
            'delivery_metadata' => ['sometimes', 'array'],
        ];
    }
}
