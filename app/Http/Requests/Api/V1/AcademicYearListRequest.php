<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class AcademicYearListRequest extends ApiFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'date_from' => ['required_with:date_to', 'date_format:Y-m-d'],
            'date_to' => ['required_with:date_from', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['sometimes', 'string', Rule::in(['planned', 'active', 'closed', 'inactive'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = $this->query();

        foreach (['page', 'per_page', 'name', 'date_from', 'date_to', 'status'] as $field) {
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
