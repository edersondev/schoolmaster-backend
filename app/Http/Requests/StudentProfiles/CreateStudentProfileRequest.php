<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CreateStudentProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'uuid'],
            'registration_number' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'current_academic_year_id' => ['nullable', 'uuid'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'enrolled_at' => ['required', 'date'],
            'guardian_associations' => ['nullable', 'array'],
            'guardian_associations.*.guardian_id' => ['required', 'uuid', 'distinct'],
            'guardian_associations.*.relationship_type' => ['required', 'string', 'max:80'],
        ];
    }
}
