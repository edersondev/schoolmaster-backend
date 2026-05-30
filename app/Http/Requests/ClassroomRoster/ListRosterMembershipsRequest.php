<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class ListRosterMembershipsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'academicPeriodId' => ['sometimes', 'uuid'],
            'status' => ['sometimes', 'string', 'in:active,ended'],
        ];
    }
}
