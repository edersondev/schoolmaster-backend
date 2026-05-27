<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountLifecycle;

use App\Http\Requests\ApiFormRequest;

final class RequestPasswordResetRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'school_id' => ['nullable', 'uuid'],
            'delivery_metadata' => ['sometimes', 'array'],
        ];
    }
}
