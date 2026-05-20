<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudentLearningSetEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entry_type' => $this->entry_type,
            'entry_reference_id' => $this->referenceUuid(),
            'sequence' => $this->sequence,
            'title' => $this->resolvedTitle(),
            'content_item' => $this->entry_type === 'content_item' && $this->relationLoaded('contentItem') && $this->contentItem !== null
                ? (new TeacherContentStudentMetadataResource($this->contentItem))->resolve()
                : null,
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
