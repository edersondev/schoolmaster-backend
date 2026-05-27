<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkGuardianLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_guardian_lifecycle_success(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['guardians.lifecycle']);
        $guardian = Guardian::query()->create(['school_id' => $school->id, 'full_name' => 'Guardian', 'relationship_type' => 'parent', 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/guardians/bulk-lifecycle', [
                'resource_type' => 'guardians',
                'action' => 'deactivate',
                'record_ids' => [$guardian->uuid],
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk();
    }
}
