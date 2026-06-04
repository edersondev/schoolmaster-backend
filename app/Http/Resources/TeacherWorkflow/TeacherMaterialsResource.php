<?php

declare(strict_types=1);

namespace App\Http\Resources\TeacherWorkflow;

use App\Http\Resources\QuestionnaireQuestionResource;
use App\Models\Questionnaire;
use App\Models\TeacherContentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TeacherMaterialsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof TeacherContentItem) {
            return $this->content($this->resource);
        }

        if ($this->resource instanceof Questionnaire) {
            return $this->questionnaire($this->resource);
        }

        return (array) $this->resource;
    }

    /**
     * @return array<string, mixed>
     */
    public static function download(TeacherContentItem $content, string $downloadUrl, \DateTimeInterface $expiresAt): array
    {
        return [
            'content_item_id' => $content->uuid,
            'file_name' => basename($content->storage_path),
            'content_type' => $content->detected_content_type ?: $content->declared_content_type,
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'download_url' => $downloadUrl,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function content(TeacherContentItem $content): array
    {
        return [
            'id' => $content->uuid,
            'school_id' => $content->school?->uuid,
            'owner_user_id' => $content->owner?->uuid,
            'folder_id' => $content->folder?->uuid,
            'title' => $content->title,
            'description' => $content->description,
            'content_type' => $content->content_type,
            'declared_content_type' => $content->declared_content_type,
            'detected_content_type' => $content->detected_content_type,
            'file_size_bytes' => $content->file_size_bytes,
            'scan_status' => $content->scan_status,
            'status' => $content->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function questionnaire(Questionnaire $questionnaire): array
    {
        return [
            'id' => $questionnaire->uuid,
            'school_id' => $questionnaire->school?->uuid,
            'owner_user_id' => $questionnaire->owner?->uuid,
            'title' => $questionnaire->title,
            'description' => $questionnaire->description,
            'status' => $questionnaire->status,
            'questions' => QuestionnaireQuestionResource::collection($questionnaire->questions)->resolve(),
        ];
    }
}
