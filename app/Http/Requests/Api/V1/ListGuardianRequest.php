<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class ListGuardianRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'full_name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'contact_email' => ['sometimes', 'string', 'min:1', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = $this->query();

        foreach (['page', 'per_page', 'full_name', 'contact_email', 'status'] as $field) {
            if (! array_key_exists($field, $query) || ! is_string($query[$field])) {
                continue;
            }

            $query[$field] = trim($query[$field]);

            if ($query[$field] === '') {
                unset($query[$field]);
            }
        }

        $this->query->replace($query);
    }
}
