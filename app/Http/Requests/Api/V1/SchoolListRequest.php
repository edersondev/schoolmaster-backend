<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;
use App\Models\School;
use App\Models\SchoolInstitutionalLookup;
use Illuminate\Validation\Rule;

final class SchoolListRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'max:120', 'regex:/^-?(name|status|created_at)(,-?(name|status|created_at))*$/'],
            'status' => ['sometimes', 'integer', Rule::in([School::STATUS_ACTIVE, School::STATUS_INACTIVE])],
            'inep_code' => ['sometimes', 'string', 'regex:/^\d+$/', 'max:32'],
            'document' => ['sometimes', 'string', 'regex:/^\d+$/', 'max:32'],
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'string', 'min:1', 'max:255'],
            'city' => ['sometimes', 'string', 'min:1', 'max:255'],
            'state' => ['sometimes', 'string', 'min:1', 'max:255'],
            'administrative_type_id' => $this->lookupRules(SchoolInstitutionalLookup::ADMINISTRATIVE_TYPE),
            'legal_nature_id' => $this->lookupRules(SchoolInstitutionalLookup::LEGAL_NATURE),
            'management_type_id' => $this->lookupRules(SchoolInstitutionalLookup::MANAGEMENT_TYPE),
            'pedagogical_approach_id' => $this->lookupRules(SchoolInstitutionalLookup::PEDAGOGICAL_APPROACH),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->query->replace($this->normalizedQuery());
    }

    /**
     * @return array<int, mixed>
     */
    private function lookupRules(string $group): array
    {
        return [
            'sometimes',
            'integer',
            Rule::exists((new SchoolInstitutionalLookup)->getTable(), 'option_id')
                ->where('group', $group)
                ->where('status', 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedQuery(): array
    {
        $query = $this->query();

        foreach (['name', 'email', 'city', 'state', 'status', 'sort', 'page', 'per_page'] as $field) {
            if (array_key_exists($field, $query) && is_string($query[$field])) {
                $query[$field] = trim($query[$field]);

                if ($query[$field] === '') {
                    unset($query[$field]);
                }
            }
        }

        foreach (['administrative_type_id', 'legal_nature_id', 'management_type_id', 'pedagogical_approach_id'] as $field) {
            if (array_key_exists($field, $query) && is_string($query[$field])) {
                $query[$field] = trim($query[$field]);

                if ($query[$field] === '') {
                    unset($query[$field]);
                }
            }
        }

        foreach (['inep_code', 'document'] as $field) {
            if (! array_key_exists($field, $query) || ! is_string($query[$field])) {
                continue;
            }

            $value = trim($query[$field]);
            if ($value === '') {
                unset($query[$field]);

                continue;
            }

            $digits = preg_replace('/\D+/', '', $value);
            $query[$field] = $digits !== '' ? $digits : $value;
        }

        return $query;
    }
}
