<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Platform;

final class ApproveSupportAccessRequest extends PlatformSupportFormRequest
{
    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', 'min:3', 'max:80'],
            'correlation_id' => ['required', 'string', 'min:8', 'max:120'],
        ];
    }
}
