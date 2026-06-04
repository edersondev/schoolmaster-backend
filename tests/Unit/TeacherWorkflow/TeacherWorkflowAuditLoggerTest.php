<?php

declare(strict_types=1);

namespace Tests\Unit\TeacherWorkflow;

use App\Models\School;
use App\Services\AuditEventService;
use App\Services\TeacherWorkflow\TeacherWorkflowAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkflowAuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_safe_metadata_strips_private_fields(): void
    {
        $logger = new TeacherWorkflowAuditLogger(new AuditEventService);

        $metadata = $logger->tenantSafeMetadata([
            'reason' => 'scan_failed',
            'file_contents' => 'secret',
            'storage_path' => '/private/path',
            'request_payload' => ['a' => 1],
        ]);

        $this->assertSame(['reason' => 'scan_failed'], $metadata);
    }

    public function test_records_teacher_workflow_event_with_target_identifiers(): void
    {
        $school = School::factory()->create();
        $teacher = $this->createTeacher($school);
        $logger = new TeacherWorkflowAuditLogger(new AuditEventService);

        $event = $logger->record(
            eventType: 'teacher_workflow.download',
            outcome: 'rejected',
            actorUserId: $teacher->id,
            schoolId: $school->id,
            targetType: 'teacher_content_item',
            targetId: 'content-uuid',
            metadata: ['reason' => 'scan_pending']
        );

        $this->assertSame('teacher_workflow.download', $event->event_type);
        $this->assertSame('teacher_content_item', $event->affected_resource_type);
        $this->assertSame('content-uuid', $event->affected_resource_id);
        $this->assertSame(['reason' => 'scan_pending'], $event->tenant_safe_metadata);
        $this->assertTrue($event->isTeacherWorkflowEvent());
    }
}
