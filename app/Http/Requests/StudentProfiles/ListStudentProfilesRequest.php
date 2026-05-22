<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;
use App\Services\StudentProfiles\StudentProfileListQuery;
use Illuminate\Validation\Rule;

final class ListStudentProfilesRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(StudentProfileListQuery::ALLOWED_STATUSES)],
            'search' => ['sometimes', 'string', 'min:1', 'max:120'],
            'sort' => ['sometimes', 'string'],
        ];
    }
}
