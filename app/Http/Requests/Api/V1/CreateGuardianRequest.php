<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;

final class CreateGuardianRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'relationship_type' => ['required', 'string', 'max:128'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'student_profile_ids' => ['nullable', 'array'],
            'student_profile_ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
