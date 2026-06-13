<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Assessment;

use App\Http\Requests\ApiFormRequest;

final class DownloadAssessmentFileRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
