<?php

declare(strict_types=1);

namespace Tests\Feature\TeacherWorkflow;

use App\Models\AuditEvent;
use App\Models\School;
use App\Models\TeacherContentItem;
use Database\Factories\TeacherWorkflowFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherContentDownloadAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_and_denied_download_attempts_are_audited_safely(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $content = TeacherWorkflowFactory::cleanContent($school, $teacher, ['storage_path' => 'private/school/content.pdf']);
        $pending = TeacherWorkflowFactory::cleanContent($school, $teacher, ['scan_status' => 'pending']);
        $headers = $this->headers($teacher, $school);

        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$content->uuid}/download")->assertOk();
        $this->withHeaders($headers)->getJson("/api/v1/teacher-content/{$pending->uuid}/download")->assertForbidden();

        $success = AuditEvent::query()->where('event_type', 'teacher_workflow.download')->where('outcome', 'success')->firstOrFail();
        $denied = AuditEvent::query()->where('event_type', 'teacher_workflow.download')->where('outcome', 'denied')->firstOrFail();

        $this->assertSame($teacher->id, $success->actor_user_id);
        $this->assertSame($school->id, $success->school_id);
        $this->assertSame(TeacherContentItem::class, $success->affected_resource_type);
        $this->assertSame($content->uuid, $success->affected_resource_id);
        $this->assertSame('scan_not_clean', $denied->tenant_safe_metadata['denial_category']);

        foreach ([$success, $denied] as $event) {
            $metadata = json_encode($event->tenant_safe_metadata, JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('private/school/content.pdf', $metadata);
            $this->assertStringNotContainsString('storage_path', $metadata);
            $this->assertStringNotContainsString('credentials', $metadata);
            $this->assertStringNotContainsString('request_payload', $metadata);
        }
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
