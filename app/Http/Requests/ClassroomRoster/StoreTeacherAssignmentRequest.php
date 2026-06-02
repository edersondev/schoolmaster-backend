<?php

declare(strict_types=1);

namespace App\Http\Requests\ClassroomRoster;

use App\Http\Requests\ApiFormRequest;

final class StoreTeacherAssignmentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'class_section_id' => ['required', 'uuid'],
            'teacher_user_id' => ['required', 'uuid'],
            'academic_period_id' => ['required', 'uuid'],
            'effective_start_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
