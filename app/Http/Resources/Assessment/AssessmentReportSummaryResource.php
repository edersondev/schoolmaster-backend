<?php

declare(strict_types=1);

namespace App\Http\Resources\Assessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AssessmentReportSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'response_count' => $this->resource['response_count'] ?? 0,
            'completion_status' => $this->resource['completion_status'] ?? 'none',
            'grading_status' => $this->resource['grading_status'] ?? 'unsubmitted',
            'average_score_percentage' => $this->resource['average_score_percentage'] ?? null,
        ];
    }
}
