<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TeacherContent\TeacherContentUploadValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TeacherContentUploadValidationTest extends TestCase
{
    public function test_accepts_supported_pdf_and_sanitizes_filename(): void
    {
        $validator = new TeacherContentUploadValidator;

        $result = $validator->validate(
            UploadedFile::fake()->create('Unsafe Name.pdf', 10, 'application/pdf'),
            'pdf',
        );

        $this->assertSame('unsafe-name.pdf', $result['safe_filename']);
    }

    public function test_rejects_executable_file(): void
    {
        $this->expectException(ValidationException::class);

        (new TeacherContentUploadValidator)->validate(
            UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
            'pdf',
        );
    }
}
