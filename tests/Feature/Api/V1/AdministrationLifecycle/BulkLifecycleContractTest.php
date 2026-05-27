<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkLifecycleContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_response_uses_documented_result_envelope(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.lifecycle']);
        $user = User::factory()->create(['school_id' => $school->id]);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users/bulk-lifecycle', [
                'resource_type' => 'users',
                'action' => 'deactivate',
                'record_ids' => [$user->uuid],
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['resource_type', 'action', 'affected_count', 'results'], 'meta']);
    }
}
