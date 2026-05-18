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

        $officeExtensions = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'];
        $officeZipContainer = $contentType === 'office_document'
            && in_array($extension, $officeExtensions, true)
            && in_array('application/zip', [$declared, $detected], true);

        return ! $officeZipContainer && (in_array($extension, $blockedExtensions, true)
            || in_array($declared, $blockedMimes, true)
            || in_array($detected, $blockedMimes, true));
    }

    private function isCompatible(string $contentType, string $extension, string $declared, string $detected): bool
    {
        return match ($contentType) {
            'pdf' => $extension === 'pdf' && $this->matchesAny([$declared, $detected], ['application/pdf']),
            'image' => $this->startsWithAny([$declared, $detected], 'image/'),
            'text' => in_array($extension, ['txt', 'csv'], true)
                && $this->matchesAny([$declared, $detected], ['text/plain', 'text/csv']),
            'office_document' => in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'], true)
                && $this->matchesAny([$declared, $detected], [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.oasis.opendocument.text',
                    'application/vnd.oasis.opendocument.spreadsheet',
                    'application/vnd.oasis.opendocument.presentation',
                    'application/zip',
                ]),
            default => false,
        };
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<int, string>  $allowed
     */
    private function matchesAny(array $values, array $allowed): bool
    {
        return collect($values)->contains(fn (string $value): bool => in_array($value, $allowed, true));
    }

    /**
     * @param  array<int, string>  $values
     */
    private function startsWithAny(array $values, string $prefix): bool
    {
        return collect($values)->contains(fn (string $value): bool => str_starts_with($value, $prefix));
    }

    private function sanitizeFilename(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $safeName = Str::slug($name) ?: 'content';

        return $extension === '' ? $safeName : $safeName.'.'.strtolower($extension);
    }
}
