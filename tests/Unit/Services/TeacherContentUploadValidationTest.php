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

    public function test_rejects_declared_and_detected_mime_mismatch(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'teacher-content-mismatch');
        file_put_contents($path, '<html>not a pdf</html>');

        $file = new class($path) extends UploadedFile
        {
            public function __construct(string $path)
            {
                parent::__construct($path, 'lesson.pdf', 'application/pdf', null, true);
            }

            public function getMimeType(): string
            {
                return 'text/html';
            }
        };

        $this->expectException(ValidationException::class);

        try {
            (new TeacherContentUploadValidator)->validate($file, 'pdf');
        } finally {
            @unlink($path);
        }
    }
}
