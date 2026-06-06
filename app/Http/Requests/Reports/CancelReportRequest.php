<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class CancelReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'reason_code' => ['required', 'string', Rule::in([
                'requested_in_error',
                'duplicate_request',
                'no_longer_needed',
                'invalid_filters',
            ])],
        ];
    }
}
