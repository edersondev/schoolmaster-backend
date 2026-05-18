<?php

declare(strict_types=1);

namespace App\Services\TeacherWorkflows;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class TeacherWorkflowListQuery
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validate(array $query): array
    {
        foreach (array_keys($query) as $field) {
            if (! in_array($field, ['page', 'per_page'], true)) {
                throw ValidationException::withMessages([
                    $field => ['This query parameter is not documented for this request.'],
                ]);
            }
        }

        return Validator::make($query, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ])->validate();
    }
}
