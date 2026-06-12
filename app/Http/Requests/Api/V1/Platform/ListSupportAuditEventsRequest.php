<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Platform;

final class ListSupportAuditEventsRequest extends PlatformSupportFormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'action' => ['sometimes', 'string', 'max:120'],
            'outcome' => ['sometimes', 'string', 'max:80'],
            'school_id' => ['sometimes', 'uuid'],
            'correlation_id' => ['sometimes', 'string', 'max:120'],
        ];
    }
}
