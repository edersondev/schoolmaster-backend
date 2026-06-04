<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuestionnaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'owner_user_id' => $this->owner?->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'questions' => QuestionnaireQuestionResource::collection($this->whenLoaded('questions'))->resolve(),
        ];
    }
}
