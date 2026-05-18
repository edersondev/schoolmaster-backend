<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Questionnaire;
use App\Models\TeacherContentItem;
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
        $model = $this->entry_type === 'content_item'
            ? TeacherContentItem::query()->find($this->entry_reference_id)
            : Questionnaire::query()->find($this->entry_reference_id);

        return $model?->uuid;
    }
}
