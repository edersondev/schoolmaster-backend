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

    public function test_bulk_user_restore_requires_view_and_manage_permissions(): void
    {
        $school = School::factory()->create();
        $users = User::factory()->count(2)->create(['school_id' => $school->id, 'status' => 'active']);
        $users->each->delete();
        $payload = [
            'resource_type' => 'users',
            'action' => 'restore',
            'record_ids' => $users->pluck('uuid')->all(),
            'effective_at' => '2026-08-22',
            'reason' => 'Approved retained identity recovery',
        ];

        foreach ([['users.lifecycle'], ['users.view'], ['users.manage']] as $permissions) {
            $this->withToken($this->bearerTokenFor($this->createSchoolAdmin($school, $permissions)))
                ->withHeader('X-School-Id', $school->uuid)
                ->postJson('/api/v1/users/bulk-lifecycle', $payload)
                ->assertForbidden();
        }

        $this->assertSame(2, User::onlyTrashed()->whereIn('uuid', $users->pluck('uuid'))->count());
        $this->assertDatabaseCount('lifecycle_histories', 0);

        $authorized = $this->createSchoolAdmin($school, ['users.view', 'users.manage']);
        $this->withToken($this->bearerTokenFor($authorized))
            ->withHeader('X-School-Id', $school->uuid)
            ->postJson('/api/v1/users/bulk-lifecycle', $payload)
            ->assertOk()
            ->assertJsonPath('data.affected_count', 2);

        $this->assertSame(2, User::query()->whereIn('uuid', $users->pluck('uuid'))->count());
    }
}
