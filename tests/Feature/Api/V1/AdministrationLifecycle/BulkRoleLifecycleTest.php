<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Role;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkRoleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_role_lifecycle_success(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['roles.lifecycle']);
        $role = Role::query()->create(['school_id' => $school->id, 'scope' => 'school', 'name' => 'Bulk Role']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/roles/bulk-lifecycle', [
                'resource_type' => 'roles',
                'action' => 'deactivate',
                'record_ids' => [$role->uuid],
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk();
    }
}
