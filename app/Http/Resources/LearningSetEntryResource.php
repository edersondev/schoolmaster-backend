<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LearningSetEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entry_type' => $this->entry_type,
            'entry_reference_id' => $this->referenceUuid(),
            'sequence' => $this->sequence,
        ];
    }

    private function referenceUuid(): ?string
    {
        if ($this->entry_type === 'content_item') {
            return $this->relationLoaded('contentItem') ? $this->contentItem?->uuid : null;
        }

        return $this->relationLoaded('questionnaire') ? $this->questionnaire?->uuid : null;
    }
}
