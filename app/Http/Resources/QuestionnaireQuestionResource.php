<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuestionnaireQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'question_type' => $this->question_type,
            'prompt' => $this->prompt,
            'options' => $this->options,
            'correct_answer' => $this->correct_answer,
            'sequence' => $this->sequence,
        ];
    }
}
