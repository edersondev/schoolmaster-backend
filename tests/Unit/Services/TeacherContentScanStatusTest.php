<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\School;
use App\Services\TeacherContent\TeacherContentScanService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TeacherContentScanStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_pending_content_clean_and_blocks_failed_availability(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);
        $service = new TeacherContentScanService;

        $this->assertSame('clean', $service->markClean($content)->scan_status);

        $failed = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'failed']);
        $this->expectException(ValidationException::class);
        $service->assertAvailable($failed);
    }
}
