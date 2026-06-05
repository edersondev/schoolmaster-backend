<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class RetryReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'reason_code' => ['sometimes', 'string', Rule::in([
                'retry_failed_generation',
                'retry_expired_output',
            ])],
        ];
    }
}
