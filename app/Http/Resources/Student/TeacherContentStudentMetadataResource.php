<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TeacherContentStudentMetadataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'content_type' => $this->content_type,
            'file_size_bytes' => $this->file_size_bytes,
            'scan_status' => $this->scan_status,
            'download_available' => $this->status === 'active' && $this->scan_status === 'clean',
        ];
    }
}
