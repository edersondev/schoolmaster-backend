<?php

declare(strict_types=1);

namespace App\Services\StudentSelfView;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class StudentSelfViewListQuery
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function validate(array $query, bool $academicPeriodRequired): array
    {
        $allowed = ['academic_period_id', 'page', 'per_page'];

        foreach (array_keys($query) as $field) {
            if (! in_array($field, $allowed, true)) {
                throw ValidationException::withMessages([$field => ['This query parameter is not documented for this request.']]);
            }
        }

        return Validator::make($query, [
            'academic_period_id' => [$academicPeriodRequired ? 'required' : 'sometimes', 'string', 'uuid'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ])->validate();
    }
}
