<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;
use App\Models\SchoolInstitutionalLookup;
use App\Rules\Cnpj;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class SchoolCreateRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['inep_code', 'document', 'phone'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = preg_replace('/\D+/', '', (string) $this->input($field));
            }
        }

        if ($this->has('email')) {
            $normalized['email'] = strtolower((string) $this->input('email'));
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['prohibited'],
            'inep_code' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/', Rule::unique('schools', 'inep_code')],
            'status' => ['required', 'integer', Rule::in([1, 0])],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'size:14', 'regex:/^[0-9]{14}$/', new Cnpj(), Rule::unique('schools', 'document')],
            'email' => ['required', 'email', 'max:100', Rule::unique('schools', 'normalized_email')],
            'phone' => ['nullable', 'string', 'max:64', 'regex:/^[0-9]*$/'],
            'website' => ['nullable', 'url', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            ...$this->addressRules(),
            ...$this->institutionalRules(),
            'timezone' => ['nullable', 'timezone'],
            'language' => ['nullable', 'string', 'max:16'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_file' => ['nullable', 'file', 'mimetypes:image/png,image/jpeg,image/webp', 'max:2048'],
        ];
    }

    private function addressRules(): array
    {
        return [
            'address' => ['required', 'array:street,number,complement,neighborhood,city,state,zip_code,country', 'required_array_keys:street,number,neighborhood,city,state,zip_code'],
            'address.street' => ['required', 'string', 'max:255'],
            'address.number' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'max_digits:10',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((int) $value > 4294967295) {
                        $fail('The address number must fit an unsigned integer.');
                    }
                },
            ],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['required', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'max:4'],
            'address.zip_code' => ['required', 'string', 'regex:/^[0-9]+$/', 'max:12'],
            'address.country' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function institutionalRules(): array
    {
        return [
            'administrative_type_id' => ['required', 'integer', $this->lookupRule(SchoolInstitutionalLookup::ADMINISTRATIVE_TYPE)],
            'legal_nature_id' => ['required', 'integer', $this->lookupRule(SchoolInstitutionalLookup::LEGAL_NATURE)],
            'management_type_id' => ['required', 'integer', $this->lookupRule(SchoolInstitutionalLookup::MANAGEMENT_TYPE)],
            'pedagogical_approach_id' => ['required', 'integer', $this->lookupRule(SchoolInstitutionalLookup::PEDAGOGICAL_APPROACH)],
            'education_level_ids' => ['required', 'array', 'min:1'],
            'education_level_ids.*' => ['integer', $this->lookupRule(SchoolInstitutionalLookup::EDUCATION_LEVEL)],
            'modality_ids' => ['required', 'array', 'min:1'],
            'modality_ids.*' => ['integer', $this->lookupRule(SchoolInstitutionalLookup::MODALITY)],
        ];
    }

    private function lookupRule(string $group): Exists
    {
        return Rule::exists('school_institutional_lookups', 'option_id')
            ->where('group', $group)
            ->where('status', 1);
    }
}
