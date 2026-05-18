<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TeacherContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'school_id' => $this->school?->uuid,
            'owner_user_id' => $this->owner?->uuid,
            'folder_id' => $this->folder?->uuid,
            'title' => $this->title,
            'content_type' => $this->content_type,
            'declared_content_type' => $this->declared_content_type,
            'detected_content_type' => $this->detected_content_type,
            'file_size_bytes' => $this->file_size_bytes,
            'scan_status' => $this->scan_status,
            'status' => $this->status,
        ];
    }
}
