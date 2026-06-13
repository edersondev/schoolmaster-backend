<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AssessmentFileRuleService
{
    public const MAX_FILE_SIZE_BYTES = 26_214_400;

    private const CATEGORY_MIME_PREFIXES = [
        'image' => ['image/'],
        'text' => ['text/plain'],
        'pdf' => ['application/pdf'],
        'office' => [
            'application/msword',
            'application/vnd.ms-',
            'application/vnd.openxmlformats-officedocument',
        ],
    ];

    public function sanitizeFilename(string $filename): string
    {
        $sanitized = Str::of(basename($filename))->replaceMatches('/[^A-Za-z0-9._-]/', '_')->trim('._-')->toString();

        if ($sanitized === '') {
            throw new InvalidArgumentException('Filename is unsafe.');
        }

        return Str::limit($sanitized, 255, '');
    }

    public function categoryForMime(string $mime): ?string
    {
        foreach (self::CATEGORY_MIME_PREFIXES as $category => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($mime, $prefix)) {
                    return $category;
                }
            }
        }

        return null;
    }

    public function assertAllowed(UploadedFile $file): string
    {
        if ($file->getSize() === false || $file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new InvalidArgumentException('File exceeds the assessment response size limit.');
        }

        $category = $this->categoryForMime((string) $file->getMimeType());

        if ($category === null) {
            throw new InvalidArgumentException('File type is not allowed for assessment responses.');
        }

        $this->sanitizeFilename($file->getClientOriginalName());

        return $category;
    }
}
