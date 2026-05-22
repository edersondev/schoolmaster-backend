<?php

declare(strict_types=1);

namespace App\Services\StudentProfiles;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class StudentProfileListQuery
{
    public const DEFAULT_SORT = 'last_name';

    /** @var array<int, string> */
    public const ALLOWED_SORTS = ['first_name', 'last_name', 'full_name', 'registration_number', 'enrolled_at', 'status', 'created_at'];

    /** @var array<int, string> */
    public const ALLOWED_STATUSES = ['active', 'inactive', 'transferred'];

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validate(array $query): array
    {
        $allowed = ['page', 'per_page', 'status', 'search', 'sort'];

        foreach (array_keys($query) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages([$field => ['This query parameter is not documented for this request.']]);
            }
        }

        return Validator::make($query, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(self::ALLOWED_STATUSES)],
            'search' => ['sometimes', 'string', 'min:1', 'max:120'],
            'sort' => ['sometimes', 'string'],
        ])->validate();
    }

    /**
     * @return array<int, array{field: string, direction: string}>
     */
    public function parseSorts(?string $sort): array
    {
        $sort ??= self::DEFAULT_SORT;

        return collect(explode(',', $sort))
            ->map(fn (string $field): string => trim($field))
            ->filter()
            ->map(function (string $field): array {
                $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
                $field = ltrim($field, '-');

                if (! in_array($field, self::ALLOWED_SORTS, true)) {
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
