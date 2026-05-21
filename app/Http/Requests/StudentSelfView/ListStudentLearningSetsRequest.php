<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentSelfView;

use App\Http\Requests\ApiFormRequest;

final class ListStudentLearningSetsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'string', 'uuid'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
