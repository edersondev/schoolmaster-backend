<?php

declare(strict_types=1);

namespace App\DTOs\TeacherContent;

use Illuminate\Http\UploadedFile;

final readonly class CreateTeacherContentData
{
    public function __construct(
        public string $title,
        public string $contentType,
        public UploadedFile $file,
        public ?string $folderId,
        public ?string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            contentType: $data['content_type'],
            file: $data['file'],
            folderId: $data['folder_id'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
