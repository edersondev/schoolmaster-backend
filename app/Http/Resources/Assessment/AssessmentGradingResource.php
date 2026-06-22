<?php

declare(strict_types=1);

namespace App\Http\Resources\Assessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AssessmentGradingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'answer_id' => $this->answer?->uuid,
            'grader_user_id' => $this->grader?->uuid,
            'grading_status' => $this->grading_status,
            'score' => $this->score === null ? null : (float) $this->score,
            'outcome' => $this->outcome,
            'feedback_summary' => $this->feedback_summary,
            'graded_at' => $this->graded_at?->toISOString(),
        ];
    }
}
