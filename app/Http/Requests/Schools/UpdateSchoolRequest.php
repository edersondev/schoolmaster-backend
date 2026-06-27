<?php

declare(strict_types=1);

namespace App\Http\Requests\Schools;

use App\Http\Requests\ApiFormRequest;
use App\Services\Addresses\AddressValidationRules;
use Illuminate\Validation\Rule;

final class UpdateSchoolRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_summary' => ['prohibited'],
            ...AddressValidationRules::create(),
        ];
    }
}
