<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Guardian;

use App\Http\Requests\ApiFormRequest;

final class GetGuardianStudentAcademicsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'academic_period_id' => ['required', 'uuid'],
        ];
    }
}
