<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentProfiles;

use App\Http\Requests\ApiFormRequest;

final class GetStudentProfileRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [];
    }
}
