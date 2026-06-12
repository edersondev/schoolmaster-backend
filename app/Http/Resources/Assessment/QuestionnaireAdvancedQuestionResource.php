<?php

declare(strict_types=1);

namespace App\Http\Resources\Assessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuestionnaireAdvancedQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'question_type' => $this->question_type,
            'prompt' => $this->prompt,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'answer_schema' => $this->answer_schema,
            'grading_rule' => $this->grading_rule,
            'visibility' => $this->visibility,
            'sequence' => $this->sequence,
        ];
    }
}
