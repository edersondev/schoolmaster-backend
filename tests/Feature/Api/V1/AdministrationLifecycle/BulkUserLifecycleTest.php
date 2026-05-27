<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\AdministrationLifecycle;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BulkUserLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_user_lifecycle_is_all_or_nothing_success(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school, ['users.lifecycle']);
        $users = User::factory()->count(2)->create(['school_id' => $school->id, 'status' => 'active']);

        $this->withToken($this->bearerTokenFor($admin))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users/bulk-lifecycle', [
                'resource_type' => 'users',
                'action' => 'deactivate',
                'record_ids' => $users->pluck('uuid')->all(),
                'effective_at' => '2026-05-26',
                'reason' => 'bulk',
            ])
            ->assertOk()
            ->assertJsonPath('data.affected_count', 2);
    }
}
