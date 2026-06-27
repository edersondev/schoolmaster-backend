<?php

declare(strict_types=1);

namespace App\Services\Addresses;

final class AddressValidationRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function create(string $prefix = 'address'): array
    {
        return [
            $prefix => [
                'sometimes',
                'nullable',
                'array:street,number,complement,neighborhood,city,state,zip_code,country',
                'required_array_keys:street,number,neighborhood,city,state,zip_code',
            ],
            $prefix.'.street' => ['required_with:'.$prefix, 'string', 'max:255'],
            $prefix.'.number' => ['required_with:'.$prefix, 'string', 'regex:/^[0-9]+$/', 'max:32'],
            $prefix.'.complement' => ['sometimes', 'nullable', 'string', 'max:255'],
            $prefix.'.neighborhood' => ['required_with:'.$prefix, 'string', 'max:255'],
            $prefix.'.city' => ['required_with:'.$prefix, 'string', 'max:255'],
            $prefix.'.state' => ['required_with:'.$prefix, 'string', 'max:255'],
            $prefix.'.zip_code' => ['required_with:'.$prefix, 'string', 'regex:/^[0-9]+$/', 'max:32'],
            $prefix.'.country' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
