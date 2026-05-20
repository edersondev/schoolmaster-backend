<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Http\Resources\Student\TeacherContentStudentMetadataResource;
use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentLearningTimelineContentMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_metadata_does_not_expose_storage_path_and_reports_availability(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);

        $metadata = (new TeacherContentStudentMetadataResource($content))->resolve();

        $this->assertArrayNotHasKey('storage_path', $metadata);
        $this->assertTrue($metadata['download_available']);
    }
}
