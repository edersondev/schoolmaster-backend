<?php

declare(strict_types=1);

namespace App\Http\Requests\AdministrationLifecycle;

use App\Http\Requests\ApiFormRequest;
use App\Services\Addresses\AddressValidationRules;
use Illuminate\Validation\Rule;

final class UpdateSchoolLifecycleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'integer', Rule::in([1, 0])],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:80'],
            'address_summary' => ['prohibited'],
            ...AddressValidationRules::create(),
        ];
    }
}
