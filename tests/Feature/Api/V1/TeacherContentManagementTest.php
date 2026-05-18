<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TeacherContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_upload_and_list_content_in_resolved_school(): void
    {
        Storage::fake('teacher_content');
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);
        TeacherWorkflowFactory::cleanContent($otherSchool, $this->createTeacher($otherSchool), ['title' => 'Other']);

        $created = $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->post('/api/v1/teacher-content', [
                'title' => 'Lesson PDF',
                'content_type' => 'pdf',
                'file' => UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Lesson PDF')
            ->assertJsonPath('data.scan_status', 'pending')
            ->json('data');

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->getJson('/api/v1/teacher-content')
            ->assertOk()
            ->assertJsonFragment(['id' => $created['id']])
            ->assertJsonMissing(['title' => 'Other']);
    }

    public function test_content_upload_rejects_cross_tenant_folder_and_unsafe_files(): void
    {
        Storage::fake('teacher_content');
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $otherFolder = TeacherWorkflowFactory::folder($otherSchool, $this->createTeacher($otherSchool));

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->post('/api/v1/teacher-content', [
                'folder_id' => $otherFolder->uuid,
                'title' => 'Invalid Folder',
                'content_type' => 'pdf',
                'file' => UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable();

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->post('/api/v1/teacher-content', [
                'title' => 'Unsafe',
                'content_type' => 'pdf',
                'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
            ])
            ->assertUnprocessable();
    }

    public function test_content_upload_does_not_persist_metadata_when_private_storage_fails(): void
    {
        config(['filesystems.disks.teacher_content.root' => '/dev/null/teacher-content']);
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);

        $this->withToken($this->bearerTokenFor($teacher))
            ->withHeader('X-School-Id', $school->uuid)
            ->post('/api/v1/teacher-content', [
                'title' => 'Unstored',
                'content_type' => 'pdf',
                'file' => UploadedFile::fake()->create('lesson.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('teacher_content_items', 0);
    }
}
