<?php

declare(strict_types=1);

namespace App\Http\Requests\Schools;

use App\Http\Requests\ApiFormRequest;
use App\Services\Addresses\AddressValidationRules;
use Illuminate\Validation\Rule;

final class StoreSchoolRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('schools', 'code')],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'address_summary' => ['prohibited'],
            ...AddressValidationRules::create(),
        ];
    }
}
