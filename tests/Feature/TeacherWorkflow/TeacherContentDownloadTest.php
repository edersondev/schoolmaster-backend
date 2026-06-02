<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\School;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherContentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_active_authorized_download_returns_tenant_safe_metadata(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['storage_path' => 'private/school/content.pdf']);

        $this->withHeaders($this->headers($teacher, $school))
            ->getJson("/api/v1/teacher-content/{$content->uuid}/download")
            ->assertOk()
            ->assertJsonPath('data.content_item_id', $content->uuid)
            ->assertJsonMissing(['storage_path' => 'private/school/content.pdf'])
            ->assertJsonMissing(['private/school/content.pdf']);
    }

    public function test_download_denies_pending_failed_inactive_deleted_cross_tenant_and_unauthorized_access(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $nonOwner = $this->createTeacher($school);
        $headers = $this->headers($teacher, $school);

        foreach ([
            TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']),
            TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'failed']),
            TeacherWorkflowFactory::cleanContent($school, $teacher, ['status' => 'inactive']),
        ] as $content) {
            $this->withHeaders($headers)
                ->getJson("/api/v1/teacher-content/{$content->uuid}/download")
                ->assertForbidden();
        }

        $deleted = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $this->withHeaders($headers)->deleteJson("/api/v1/teacher-content/{$deleted->uuid}")->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$deleted->uuid}/download")->assertForbidden();

        $crossTenant = TeacherWorkflowFactory::cleanContent($otherSchool, $this->createTeacher($otherSchool));
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$crossTenant->uuid}/download")->assertNotFound();

        $owned = TeacherWorkflowFactory::cleanContent($school, $teacher);
        $this->withHeaders($this->headers($nonOwner, $school))->getJson("/api/v1/teacher-content/{$owned->uuid}/download")->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function headers($user, School $school): array
    {
        return [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($user),
            'X-School-Id' => $school->uuid,
        ];
    }
}
