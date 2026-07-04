<?php

declare(strict_types=1);

namespace App\Http\Requests\Schools;

use App\Http\Requests\ApiFormRequest;
use App\Rules\Cnpj;
use App\Services\Addresses\AddressValidationRules;
use Illuminate\Validation\Rule;

final class StoreSchoolRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $this->merge([
                'cnpj' => preg_replace('/\D+/', '', (string) $this->input('cnpj')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'size:14', 'regex:/^[0-9]{14}$/', new Cnpj(), Rule::unique('schools', 'cnpj')],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'address_summary' => ['prohibited'],
            ...AddressValidationRules::create(),
        ];
    }
}
