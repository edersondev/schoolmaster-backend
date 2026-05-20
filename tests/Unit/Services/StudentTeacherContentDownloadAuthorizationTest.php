<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\TenantContext;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentSelfView\StudentTeacherContentDownloadService;
use App\Services\TenantContextService;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentTeacherContentDownloadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_content_returns_null(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $studentUser = User::factory()->create(['school_id' => $school->id]);
        StudentProfile::query()->create(['school_id' => $school->id, 'user_id' => $studentUser->id, 'status' => 'active']);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $service = new StudentTeacherContentDownloadService(new TenantContextService);

        $this->expectException(ModelNotFoundException::class);

        $service->resolveDownload($studentUser, new TenantContext($school, 'test', 'resolved'), $content->uuid);
    }
}
