<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Guardian;

use App\Http\Requests\ApiFormRequest;

final class ListGuardianStudentsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
