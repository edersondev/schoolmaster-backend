<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountLifecycle;

use App\Http\Requests\ApiFormRequest;

final class AccountRecoveryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:unlock,reactivate'],
            'reason' => ['sometimes', 'string', 'max:500'],
        ];
    }
}
