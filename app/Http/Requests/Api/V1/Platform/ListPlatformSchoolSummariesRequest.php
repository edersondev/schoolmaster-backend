<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Platform;

final class ListPlatformSchoolSummariesRequest extends PlatformSupportFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'sort' => ['sometimes', 'string', 'max:120'],
        ];
    }
}
