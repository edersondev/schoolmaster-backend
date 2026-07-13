<?php

declare(strict_types=1);

namespace Tests\Feature\SystemAdminMasterAccess;

use App\Models\AuditEvent;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

final class MasterAccessAuditEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_protected_state_changing_route_has_master_audit_coverage(): void
    {
        $publicRoutes = [
            'api.v1.auth.login',
            'api.v1.auth.password-reset-requests',
            'api.v1.auth.password-resets',
            'api.v1.account-invitations.setup',
        ];
        $protectedMutations = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(static fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/'))
            ->filter(static fn (Route $route): bool => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [])
            ->reject(static fn (Route $route): bool => in_array($route->getName(), $publicRoutes, true));

        $this->assertNotEmpty($protectedMutations);

        foreach ($protectedMutations as $route) {
            $this->assertContains('schoolmaster.master_access_audit', $route->gatherMiddleware(), $route->getName() ?? $route->uri());
        }
    }

    public function test_create_update_and_lifecycle_actions_record_master_access_evidence(): void
    {
        $school = School::factory()->create();
        $master = $this->createSystemAdministrator();
        $headers = [
            'Authorization' => 'Bearer '.$this->bearerTokenFor($master),
            'X-School-Id' => $school->uuid,
        ];

        $yearId = $this->withHeaders($headers)->postJson('/api/v1/academic-years', [
            'name' => 'Master Audit Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ])->assertCreated()->json('data.id');

        $this->withHeaders($headers)->patchJson('/api/v1/academic-years/'.$yearId, [
            'name' => 'Master Audit Year Updated',
        ])->assertOk();

        $this->withHeaders($headers)->postJson('/api/v1/academic-years/'.$yearId.'/deactivate', [
            'effective_at' => '2026-07-13',
            'reason' => 'Audit master lifecycle',
        ])->assertOk();

        $this->assertSame(3, AuditEvent::query()
            ->where('event_type', 'master_access.mutation')
            ->where('actor_user_id', $master->id)
            ->where('school_id', $school->id)
            ->where('tenant_safe_metadata->master_access_used', true)
            ->count());

        $this->assertDatabaseHas('lifecycle_histories', [
            'actor_user_id' => $master->id,
            'school_id' => $school->id,
            'metadata_summary->master_access_used' => true,
        ]);
    }
}
