<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateAttendanceRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'uuid'],
            'academic_period_id' => ['required', 'uuid'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'attendance_status' => ['required', 'string', Rule::in(['present', 'absent', 'late', 'excused', 'remote', 'suspended'])],
        ];
    }
}
