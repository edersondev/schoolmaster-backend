<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;

final class DeleteAdministrationResourceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'effective_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:1', 'max:500'],
        ];
    }
}
