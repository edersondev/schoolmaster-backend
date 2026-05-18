<?php

declare(strict_types=1);

namespace App\Services\TeacherContent;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TeacherContentUploadValidator
{
    private const MAX_BYTES = 26_214_400;

    /**
     * @return array{declared_content_type: string, detected_content_type: string, file_size_bytes: int, safe_filename: string}
     */
    public function validate(UploadedFile $file, string $contentType): array
    {
        $declared = (string) $file->getClientMimeType();
        $detected = (string) ($file->getMimeType() ?: $declared);
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($file->getSize() === false || $file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => ['The file may not be greater than 25 MB.']]);
        }

        if ($this->isExecutableOrArchive($contentType, $extension, $declared, $detected)) {
            throw ValidationException::withMessages(['file' => ['Executable and archive files are not supported.']]);
        }

        if (! $this->isCompatible($contentType, $extension, $declared, $detected)) {
            throw ValidationException::withMessages(['file' => ['The declared content type does not match the detected file type.']]);
        }

        return [
            'declared_content_type' => $declared,
            'detected_content_type' => $detected,
            'file_size_bytes' => (int) $file->getSize(),
            'safe_filename' => $this->sanitizeFilename($file->getClientOriginalName()),
        ];
    }

    public function storagePath(string $schoolUuid, string $contentUuid, string $safeFilename): string
    {
        $filename = basename($safeFilename);

        return $schoolUuid.'/'.$contentUuid.'/'.$filename;
    }

    private function isExecutableOrArchive(string $contentType, string $extension, string $declared, string $detected): bool
    {
        $blockedExtensions = ['exe', 'msi', 'bat', 'cmd', 'com', 'sh', 'app', 'jar', 'zip', 'rar', '7z', 'tar', 'gz'];
        $blockedMimes = [
            'application/x-msdownload',
            'application/x-msdos-program',
            'application/x-sh',
            'application/x-executable',
            'application/java-archive',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-tar',
        ];

        return in_array($extension, $blockedExtensions, true)
            || ($this->isBlockedMime($declared, $contentType, $extension, $blockedMimes))
            || ($this->isBlockedMime($detected, $contentType, $extension, $blockedMimes));
    }

    private function isCompatible(string $contentType, string $extension, string $declared, string $detected): bool
    {
        return $this->hasCompatibleExtension($contentType, $extension)
            && $this->isCompatibleMime($contentType, $extension, $declared)
            && $this->isCompatibleMime($contentType, $extension, $detected);
    }

    private function hasCompatibleExtension(string $contentType, string $extension): bool
    {
        return match ($contentType) {
            'pdf' => $extension === 'pdf',
            'image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true),
            'text' => in_array($extension, ['txt', 'csv'], true),
            'office_document' => in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'], true),
            default => false,
        };
    }

    private function isCompatibleMime(string $contentType, string $extension, string $mime): bool
    {
        return match ($contentType) {
            'pdf' => $mime === 'application/pdf',
            'image' => str_starts_with($mime, 'image/'),
            'text' => in_array($mime, ['text/plain', 'text/csv'], true),
            'office_document' => $this->isOfficeDocumentMime($extension, $mime),
            default => false,
        };
    }

    private function isBlockedMime(string $mime, string $contentType, string $extension, array $blockedMimes): bool
    {
        return in_array($mime, $blockedMimes, true)
            && ! ($contentType === 'office_document' && $this->isOfficeZipContainer($extension, $mime));
    }

    private function isOfficeDocumentMime(string $extension, string $mime): bool
    {
        $allowedMimes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
        ];

        return in_array($mime, $allowedMimes, true) || $this->isOfficeZipContainer($extension, $mime);
    }

    private function isOfficeZipContainer(string $extension, string $mime): bool
    {
        return $mime === 'application/zip'
            && in_array($extension, ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'], true);
    }

    private function sanitizeFilename(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $safeName = Str::slug($name) ?: 'content';

        return $extension === '' ? $safeName : $safeName.'.'.strtolower($extension);
    }
}
