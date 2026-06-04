<?php

declare(strict_types=1);

namespace Tests\Feature\GuardianSelfService;

final class GuardianAuditEventsTest extends GuardianSelfServiceTestCase
{
    public function test_guardian_reads_and_denials_create_tenant_safe_audit_events(): void
    {
        [$school, , $guardian, $guardianUser, $student, $period] = $this->guardianContext();

        $this->withHeaders($this->headers($guardianUser, $school))->getJson('/api/v1/guardian/students')->assertOk();
        $this->withHeaders($this->headers($guardianUser, $school))->getJson("/api/v1/guardian/students/{$student->uuid}")->assertOk();
        $this->withHeaders($this->headers($guardianUser, $school))->getJson("/api/v1/guardian/students/{$student->uuid}/academics?academic_period_id={$period->uuid}")->assertOk();
        $this->withHeaders($this->headers($guardianUser, $school))->getJson("/api/v1/guardian/students/{$student->uuid}/contacts")->assertOk();

        $unlinkedGuardianUser = \App\Models\User::factory()->create(['school_id' => $school->id, 'status' => 'active']);
        $this->withHeaders($this->headers($unlinkedGuardianUser, $school))->getJson('/api/v1/guardian/students')->assertForbidden();

        $otherSchool = \App\Models\School::factory()->create();
        $otherStudent = $this->student($otherSchool);
        $this->withHeaders($this->headers($guardianUser, $school))->getJson("/api/v1/guardian/students/{$otherStudent->uuid}")->assertNotFound();

        $this->withToken($this->bearerTokenFor($guardianUser))
            ->withHeader('X-School-Id', $otherSchool->uuid)
            ->getJson('/api/v1/guardian/students')
            ->assertForbidden();

        foreach (['student_list', 'student_detail', 'academic_summary', 'contact_view'] as $action) {
            $this->assertDatabaseHas('audit_events', [
                'event_type' => 'guardian_self_service.'.$action,
                'actor_user_id' => $guardianUser->id,
                'school_id' => $school->id,
                'outcome' => 'allowed',
            ]);
        }

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'guardian_self_service.student_list',
            'actor_user_id' => $unlinkedGuardianUser->id,
            'school_id' => $school->id,
            'outcome' => 'denied',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'guardian_self_service.student_detail',
            'actor_user_id' => $guardianUser->id,
            'school_id' => $school->id,
            'affected_resource_id' => null,
            'outcome' => 'denied',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'guardian_self_service.student_list',
            'actor_user_id' => $guardianUser->id,
            'school_id' => null,
            'outcome' => 'denied',
        ]);

        $this->assertDatabaseMissing('audit_events', [
            'affected_resource_id' => $otherStudent->uuid,
        ]);

        $this->assertDatabaseMissing('audit_events', [
            'tenant_safe_metadata->contact_email' => $guardian->contact_email,
        ]);
    }
}
