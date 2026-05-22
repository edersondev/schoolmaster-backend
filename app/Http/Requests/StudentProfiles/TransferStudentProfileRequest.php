<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;

final class TransferStudentProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'effective_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'destination_school_id' => ['nullable', 'uuid'],
            'destination_student_profile_id' => ['nullable', 'uuid'],
        ];
    }
}
