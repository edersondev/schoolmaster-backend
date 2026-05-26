<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;

final class UpdateGuardianLifecycleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'relationship_type' => ['sometimes', 'string', 'max:80'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:80'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
    }
}
