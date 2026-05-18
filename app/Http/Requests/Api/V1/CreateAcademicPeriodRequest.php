<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;

final class CreateAcademicPeriodRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'sequence' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }
}
