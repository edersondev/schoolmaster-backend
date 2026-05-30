<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class UpdateTeacherAssignmentStatusRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:inactive'],
            'effective_end_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:1', 'max:500'],
        ];
    }
}
