<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\AuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MasterAccessAuditContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_marker_records_actor_action_target_outcome_timestamp_and_selected_school(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $master = $this->createSystemAdministrator();

        $this->withToken($this->bearerTokenFor($master))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/academic-years', [
                'name' => 'Audit Context Year',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ])
            ->assertCreated();

        $event = AuditEvent::query()->where('event_type', 'master_access.mutation')->sole();

        $this->assertSame($master->id, $event->actor_user_id);
        $this->assertSame($school->id, $event->school_id);
        $this->assertNotSame($otherSchool->id, $event->school_id);
        $this->assertSame('route', $event->affected_resource_type);
        $this->assertSame('api.v1.academic-years.store', $event->affected_resource_id);
        $this->assertSame('success', $event->outcome);
        $this->assertSame('POST', $event->tenant_safe_metadata['http_method']);
        $this->assertNotNull($event->occurred_at);
    }
}
