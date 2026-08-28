<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountLifecycle;

use App\Http\Requests\ApiFormRequest;

final class RequestPasswordDeliveryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [];
    }
}
