<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\ApiFormRequest;

final class CreateGuardianUserLinkRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
