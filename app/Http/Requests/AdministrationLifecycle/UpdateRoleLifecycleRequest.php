<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;

final class UpdateRoleLifecycleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'permission_ids' => ['sometimes', 'array', 'min:1'],
            'permission_ids.*' => ['string', 'uuid'],
        ];
    }
}
