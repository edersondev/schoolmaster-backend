<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class UpdateStudentProfileStatusRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'effective_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
