<?php

declare(strict_types=1);

namespace App\Services\School;

use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SchoolLogoService
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    /** @var array<int, string> */
    private const ALLOWED_MIME_TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    public function store(UploadedFile $file, School $school): string
    {
        $this->assertValid($file);

        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => $file->extension(),
        };

        $filename = Str::uuid().'.'.$extension;

        return $file->storeAs('school-logos/'.$school->uuid, $filename, 'public');
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function assertValid(UploadedFile $file): void
    {
        if ($file->getSize() !== null && $file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('School logo must not be larger than 2 MB.');
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('School logo must be a PNG, JPEG, or WebP file.');
        }
    }
}
