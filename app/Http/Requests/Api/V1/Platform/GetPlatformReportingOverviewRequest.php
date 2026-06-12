<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Platform;

final class GetPlatformReportingOverviewRequest extends PlatformSupportFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'school_status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'report_source' => ['sometimes', 'string', 'in:built_in,custom'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ];
    }
}
