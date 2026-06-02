<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class BatchAddRosterMembershipsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'uuid'],
            'effective_start_date' => ['required', 'date_format:Y-m-d'],
            'student_profile_ids' => ['required', 'array', 'min:1', 'max:100'],
            'student_profile_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
