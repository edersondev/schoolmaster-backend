<?php

declare(strict_types=1);

namespace Tests\Unit\School;

use App\Models\School;
use App\Services\School\SchoolLogoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class SchoolLogoServiceTest extends TestCase
{
    public function test_store_accepts_supported_logo_and_writes_under_school_directory(): void
    {
        Storage::fake('public');
        $school = new School(['uuid' => (string) Str::uuid()]);

        $path = (new SchoolLogoService())->store(
            UploadedFile::fake()->create('logo.png', 16, 'image/png'),
            $school,
        );

        $this->assertStringStartsWith('school-logos/'.$school->uuid.'/', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_store_rejects_unsupported_logo_mime_type(): void
    {
        Storage::fake('public');
        $school = new School(['uuid' => (string) Str::uuid()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('School logo must be a PNG, JPEG, or WebP file.');

        (new SchoolLogoService())->store(
            UploadedFile::fake()->create('logo.svg', 16, 'image/svg+xml'),
            $school,
        );
    }

    public function test_store_rejects_executable_logo_uploads(): void
    {
        Storage::fake('public');
        $school = new School(['uuid' => (string) Str::uuid()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('School logo must be a PNG, JPEG, or WebP file.');

        (new SchoolLogoService())->store(
            UploadedFile::fake()->create('logo.exe', 16, 'application/x-msdownload'),
            $school,
        );
    }

    public function test_store_rejects_logo_larger_than_two_megabytes(): void
    {
        Storage::fake('public');
        $school = new School(['uuid' => (string) Str::uuid()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('School logo must not be larger than 2 MB.');

        (new SchoolLogoService())->store(
            UploadedFile::fake()->create('large.png', 2049, 'image/png'),
            $school,
        );
    }

    public function test_delete_removes_existing_logo_path(): void
    {
        Storage::fake('public');
        $path = 'school-logos/example/logo.png';
        Storage::disk('public')->put($path, 'logo');

        (new SchoolLogoService())->delete($path);

        Storage::disk('public')->assertMissing($path);
    }
}
