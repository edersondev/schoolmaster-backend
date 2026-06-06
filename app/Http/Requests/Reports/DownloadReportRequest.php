<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;

final class DownloadReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['pdf', 'csv', 'xlsx'])],
        ];
    }
}
