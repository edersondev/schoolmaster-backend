<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ValidatesListQuery
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<int, string>  $allowedSorts
     * @param  array<int, string>  $allowedStatuses
     * @return array<string, mixed>
     */
    private function validateListQuery(array $query, array $allowedSorts = [], array $allowedStatuses = ['active', 'inactive']): array
    {
        $allowed = ['page', 'per_page'];

        $rules = [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];

        if ($allowedStatuses !== []) {
            $allowed[] = 'status';
            $rules['status'] = ['sometimes', 'string', Rule::in($allowedStatuses)];
        }

        if ($allowedSorts !== []) {
            $allowed[] = 'sort';
            $rules['sort'] = ['sometimes', 'string'];
        }

        foreach (array_keys($query) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages([$field => ['This query parameter is not documented for this request.']]);
            }
        }

        return Validator::make($query, $rules)->validate();
    }

    /**
     * @param  array<int, string>  $allowedSorts
     * @return array<int, array{field: string, direction: string}>
     */
    private function parseSorts(?string $sort, array $allowedSorts, string $defaultSort): array
    {
        $sort ??= $defaultSort;

        return collect(explode(',', $sort))
            ->map(fn (string $field): string => trim($field))
            ->filter()
            ->map(function (string $field) use ($allowedSorts): array {
                $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
                $field = ltrim($field, '-');

                if (! in_array($field, $allowedSorts, true)) {
                    throw ValidationException::withMessages([
                        'sort' => ['The selected sort is invalid.'],
                    ]);
                }

                return ['field' => $field, 'direction' => $direction];
            })
            ->values()
            ->all();
    }
}
