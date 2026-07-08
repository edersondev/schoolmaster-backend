<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\AddressLookup;

use App\Http\Requests\ApiFormRequest;

final class LookupAddressRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'zip_code' => preg_replace('/\D+/', '', (string) $this->route('zipCode')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'zip_code' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/'],
        ];
    }
}
